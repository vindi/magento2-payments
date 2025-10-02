<?php

declare(strict_types=1);

namespace Vindi\VP\Cron;

use Vindi\VP\Model\PaymentLinkFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class CancelOrdersWithExpiredLinks
{
    /**
     * @var PaymentLinkFactory
     */
    private $paymentLinkFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PaymentLinkFactory $paymentLinkFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagement
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentLinkFactory $paymentLinkFactory,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->paymentLinkFactory = $paymentLinkFactory;
        $this->orderRepository = $orderRepository;
        $this->orderManagement = $orderManagement;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Execute the cron job
     */
    public function execute(): void
    {
        try {
            $currentDate = $this->dateTime->gmtDate();
            $paymentLinkCollection = $this->paymentLinkFactory->create()->getCollection()
                ->addFieldToFilter('expired_at', ['notnull' => true])
                ->addFieldToFilter('expired_at', ['lt' => date('Y-m-d H:i:s', strtotime('-30 days', strtotime($currentDate)))]);

            foreach ($paymentLinkCollection as $paymentLink) {
                $orderId = $paymentLink->getOrderId();
                $order = $this->orderRepository->get($orderId);

                if ($order && $order->canCancel()) {
                    $this->orderManagement->cancel($orderId);
                    $this->logger->info(sprintf('Order ID %s has been canceled due to expired payment link.', $orderId));

                    if ($paymentLink->getStatus() !== 'processed') {
                        $paymentLink->setStatus('processed');
                        $paymentLink->save();
                        $this->logger->info(sprintf('Payment link for order ID %s has been updated to "processed".', $orderId));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in canceling orders with expired links: ' . $e->getMessage());
        }
    }
}
