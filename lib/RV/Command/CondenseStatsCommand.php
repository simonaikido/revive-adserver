<?php

namespace RV\Command;

use RV\Dal\Statistics\Condense;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'condense-stats', description: 'Condense statistics in order to free database space')]
class CondenseStatsCommand extends AbstractReviveCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not request permission to proceed')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry-run operation')
            ->addOption('daily-range', 'd', InputOption::VALUE_REQUIRED, 'Number of months to keep before condensing to daily', '12')
            ->addOption('monthly-range', 'm', InputOption::VALUE_REQUIRED, 'Number of months to keep before condensing to monthly (0 to disable)', '36')
            ->addOption('batches', 'b', InputOption::VALUE_REQUIRED, 'Number of batches to process (0 for unlimited)', 0)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRevive($input, $output);

        require_once MAX_PATH . '/lib/OA/DB.php';
        require_once MAX_PATH . '/lib/OA/Permission.php';

        $monthly = (int) $input->getOption('monthly-range');
        $daily = (int) $input->getOption('daily-range');
        $batches = (int) $input->getOption('batches');
        $dryRun = (bool) $input->getOption('dry-run');

        $message = "Monthly: {$monthly} - Daily: {$daily}";

        if ($batches > 0) {
            $message .= " - Batches: {$batches}";
        }

        if ($dryRun) {
            $message .= " <error>(DRY-RUN)</error>";
        }

        $output->writeln("<info>{$message}</info>", OutputInterface::VERBOSITY_VERBOSE);

        if (!$this->askQuestion($input, $output, 'Are you sure you want to proceed?')) {
            return self::INVALID;
        }

        $condense = new Condense(new SymfonyStyle($input, $output));
        $condense->start($monthly, $daily, $batches, $dryRun);

        return self::SUCCESS;
    }

}
