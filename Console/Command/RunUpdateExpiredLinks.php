<?php

declare(strict_types=1);

namespace Vindi\VP\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vindi\VP\Cron\UpdateExpiredLinks;

class RunUpdateExpiredLinks extends Command
{
    /**
     * @var UpdateExpiredLinks
     */
    private $updateExpiredLinks;

    /**
     * @param UpdateExpiredLinks $updateExpiredLinks
     * @param string|null $name
     */
    public function __construct(
        UpdateExpiredLinks $updateExpiredLinks,
        $name = null
    ) {
        $this->updateExpiredLinks = $updateExpiredLinks;
        parent::__construct($name);
    }

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName('vindi:vp:update-expired-links')
            ->setDescription('Manually run the cron to update expired payment links');
        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->updateExpiredLinks->execute();
            $output->writeln('<info>Expired payment links updated successfully.</info>');
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error updating expired payment links: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
