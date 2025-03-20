<?php
declare(strict_types=1);

namespace Vindi\VP\Cron;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\ResourceConnection;
use Vindi\VP\Logger\Logger;
use Vindi\VP\Helper\Order as HelperOrder;

class ProcessCallbackQueue
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Logger
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
     * Constructor.
     *
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param HelperOrder $helperOrder
     * @param FileDriver $fileDriver
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        HelperOrder $helperOrder,
        FileDriver $fileDriver
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->helperOrder = $helperOrder;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Execute Cron Job to process callback queue.
     *
     * @return void
     */
    public function execute(): void
    {
        $lockFile = BP . '/var/locks/process_callback_queue.lock';
        $fp = $this->fileDriver->fileOpen($lockFile, 'w+');

        if (!$fp) {
            $this->logger->error(__('Unable to open lock file: %1', $lockFile));
            return;
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            $this->logger->info(__('Cron job already running. Exiting.'));
            return;
        }

        $this->logger->info(__('Lock acquired. Starting callback processing.'));

        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('vindi_vp_callback');

            $select = $connection->select()
                ->from($tableName)
                ->where('queue_status = ?', 'pending')
                ->where('attempts < ?', 3);

            $callbacks = $connection->fetchAll($select);
            $this->logger->info(__('Found %1 pending callbacks to process.', count($callbacks)));

            foreach ($callbacks as $callback) {
                $callbackId = $callback['entity_id'];
                $this->logger->info(__('Processing callback ID: %1', $callbackId));
                $attempts = (int)$callback['attempts'];

                // Atualiza o número de tentativas antes de processar
                $connection->update(
                    $tableName,
                    ['attempts' => $attempts + 1],
                    ['entity_id = ?' => $callbackId]
                );

                try {
                    $params = json_decode($callback['payload'], true);
                    if (!is_array($params)) {
                        throw new \Exception((string) __('Invalid JSON payload for callback ID %1', $callbackId));
                    }

                    if (isset($params['transaction'])) {
                        $transaction = $params['transaction'];
                        $orderIncrementId = $transaction['order_number'] ?? ($transaction['free'] ?? '');
                        $order = $this->helperOrder->loadOrder($orderIncrementId);

                        if ($order && $order->getId()) {
                            $vindiStatus = $transaction['status_id'] ?? '';
                            $amount = $transaction['price_original'] ?? $order->getGrandTotal();
                            $this->helperOrder->updateOrder($order, $vindiStatus, $transaction, (float)$amount, true);
                            $this->logger->info(__('Callback ID %1 processed successfully. Order %2 updated.', $callbackId, $orderIncrementId));
                        } else {
                            $this->logger->warning(__('Order not found for callback ID %1 with order number %2.', $callbackId, $orderIncrementId));
                        }
                    } else {
                        $this->logger->warning(__('Transaction data missing in callback ID %1.', $callbackId));
                    }

                    $connection->update(
                        $tableName,
                        ['queue_status' => 'executed'],
                        ['entity_id = ?' => $callbackId]
                    );
                } catch (\Exception $e) {
                    $this->logger->error(__('Error processing callback ID %1: %2', $callbackId, $e->getMessage()));

                    if (($attempts + 1) >= 3) {
                        $connection->update(
                            $tableName,
                            ['queue_status' => 'failed'],
                            ['entity_id = ?' => $callbackId]
                        );
                        $this->logger->error(__('Callback ID %1 marked as failed after %2 attempts.', $callbackId, $attempts + 1));
                    }
                }
            }
            $this->logger->info(__('Finished processing callbacks.'));
        } catch (\Exception $e) {
            $this->logger->error(__('Error executing cron job: %1', $e->getMessage()));
        } finally {
            flock($fp, LOCK_UN);
            $this->fileDriver->fileClose($fp);
            $this->logger->info(__('Lock released.'));
        }
    }
}
