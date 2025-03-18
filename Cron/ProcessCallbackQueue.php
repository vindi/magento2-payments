<?php

namespace Vindi\VP\Cron;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Vindi\VP\Helper\Order as HelperOrder;

class ProcessCallbackQueue
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * Constructor
     *
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param HelperOrder $helperOrder
     * @param FileDriver $fileDriver
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        HelperOrder $helperOrder,
        FileDriver $fileDriver
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->helperOrder = $helperOrder;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Execute Cron Job to process callback queue
     *
     * @return void
     */
    public function execute()
    {
        $lockFile = BP . '/var/locks/process_callback_queue.lock';

        $fp = $this->fileDriver->fileOpen($lockFile, 'w+');
        if (!$fp) {
            $this->logger->error(__('Unable to open lock file: %1', $lockFile));
            return;
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            return;
        }

        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('vindi_vp_callback');

            $select = $connection->select()
                ->from($tableName)
                ->where('queue_status = ?', 'pending')
                ->where('attempts < ?', 3);

            $callbacks = $connection->fetchAll($select);

            foreach ($callbacks as $callback) {
                $attempts = (int)$callback['attempts'];

                $connection->update(
                    $tableName,
                    ['attempts' => $attempts + 1],
                    ['entity_id = ?' => $callback['entity_id']]
                );

                try {
                    $params = json_decode($callback['payload'], true);
                    if (!is_array($params)) {
                        throw new \Exception(__('Invalid JSON payload for callback ID %1', $callback['entity_id']));
                    }

                    if (isset($params['transaction'])) {
                        $transaction = $params['transaction'];
                        $orderIncrementId = $transaction['order_number'] ?? ($transaction['free'] ?? '');
                        $order = $this->helperOrder->loadOrder($orderIncrementId);

                        if ($order && $order->getId()) {
                            $vindiStatus = $transaction['status_id'] ?? '';
                            $amount = $transaction['price_original'] ?? $order->getGrandTotal();
                            $this->helperOrder->updateOrder($order, $vindiStatus, $transaction, $amount, true);
                        }
                    }

                    $connection->update(
                        $tableName,
                        ['queue_status' => 'executed'],
                        ['entity_id = ?' => $callback['entity_id']]
                    );
                } catch (\Exception $e) {
                    $this->logger->error(__('Error processing callback ID %1: %2', $callback['entity_id'], $e->getMessage()));

                    if (($attempts + 1) >= 3) {
                        $connection->update(
                            $tableName,
                            ['queue_status' => 'failed'],
                            ['entity_id = ?' => $callback['entity_id']]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__('Error executing cron job: %1', $e->getMessage()));
        } finally {
            flock($fp, LOCK_UN);
            $this->fileDriver->fileClose($fp);
        }
    }
}
