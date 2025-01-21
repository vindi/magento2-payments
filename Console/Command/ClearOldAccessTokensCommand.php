<?php

declare(strict_types=1);

namespace Vindi\VP\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vindi\VP\Cron\ClearOldAccessTokens;

class ClearOldAccessTokensCommand extends Command
{
    /**
     * @var ClearOldAccessTokens
     */
    private ClearOldAccessTokens $clearOldAccessTokens;

    /**
     * Constructor.
     *
     * @param ClearOldAccessTokens $clearOldAccessTokens
     */
    public function __construct(
        ClearOldAccessTokens $clearOldAccessTokens
    ) {
        parent::__construct();
        $this->clearOldAccessTokens = $clearOldAccessTokens;
    }

    /**
     * Configure command details.
     */
    protected function configure()
    {
        $this->setName('vindi:vp:clear-old-access-tokens')
            ->setDescription('Clear expired access tokens for Vindi VP module.');
    }

    /**
     * Execute the console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting expired access tokens clearance...</info>');

        try {
            $this->clearOldAccessTokens->execute();
            $output->writeln('<info>Expired access tokens cleared successfully.</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
