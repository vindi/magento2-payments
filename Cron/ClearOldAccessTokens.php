<?php

declare(strict_types=1);

namespace Vindi\VP\Cron;

use Vindi\VP\Model\ResourceModel\AccessTokenRepository;
use Psr\Log\LoggerInterface;

class ClearOldAccessTokens
{
    /**
     * @var AccessTokenRepository
     */
    private AccessTokenRepository $accesTokenRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param AccessTokenRepository $accesTokenRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        AccessTokenRepository $accesTokenRepository,
        LoggerInterface $logger
    ) {
        $this->accesTokenRepository = $accesTokenRepository;
        $this->logger = $logger;
    }

    /**
     * Execute the cron job to update the status of expired payment links.
     */
    public function execute(): void
    {
        try {
            $this->accesTokenRepository->deleteExpired();
            $this->logger->info('Expired payment links updated successfully.');
        } catch (\Exception $e) {
            $this->logger->error('Error updating expired payment links: ' . $e->getMessage());
        }
    }
}
