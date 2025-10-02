<?php

declare(strict_types=1);

namespace Vindi\VP\Cron;

use Vindi\VP\Model\PaymentLinkService;
use Psr\Log\LoggerInterface;

class UpdateExpiredLinks
{
    /**
     * @var PaymentLinkService
     */
    private $paymentLinkService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PaymentLinkService $paymentLinkService
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentLinkService $paymentLinkService,
        LoggerInterface $logger
    ) {
        $this->paymentLinkService = $paymentLinkService;
        $this->logger = $logger;
    }

    /**
     * Execute the cron job to update the status of expired payment links.
     */
    public function execute(): void
    {
        try {
            $this->paymentLinkService->updateExpiredPaymentLinks();
            $this->logger->info('Expired payment links updated successfully.');
        } catch (\Exception $e) {
            $this->logger->error('Error updating expired payment links: ' . $e->getMessage());
        }
    }
}
