<?php

namespace Vindi\VP\Console\Command;

use Vindi\VP\Cron\ProcessCallbackQueue;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

/**
 * Command to execute the vindi_vp_process_callback_queue cron job manually.
 */
class ProcessCallbackQueueCommand extends Command
{
    /**
     * @var ProcessCallbackQueue
     */
    private $processCallbackQueue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ProcessCallbackQueue $processCallbackQueue
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProcessCallbackQueue $processCallbackQueue,
        LoggerInterface $logger
    ) {
        $this->processCallbackQueue = $processCallbackQueue;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * Configure the command options and description.
     */
    protected function configure()
    {
        $this->setName('vindi:process-callback-queue')
            ->setDescription('Executes the vindi_vp_process_callback_queue cron job manually.');
        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->processCallbackQueue->execute();
            $output->writeln('<info>Callback queue processing executed successfully.</info>');
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Error executing callback queue processing: ' . $e->getMessage());
            $output->writeln('<error>Error executing callback queue processing: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
