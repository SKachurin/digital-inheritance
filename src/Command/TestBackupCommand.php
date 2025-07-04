<?php

namespace App\Command;

use App\Service\BackupDatabaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-backup')]
class TestBackupCommand extends Command
{
    public function __construct(private readonly BackupDatabaseService $backupService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running DB backup...');
        $this->backupService->run();
        $output->writeln('Backup done.');
        return Command::SUCCESS;
    }
}
