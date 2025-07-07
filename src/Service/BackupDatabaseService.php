<?php

namespace App\Service;

use App\Controller\PythonServiceController;
use Psr\Log\LoggerInterface;

class BackupDatabaseService
{
    private string $dbHost;
    private string $dbUser;
    private string $dbPass;
    private string $dbName;

    public function __construct(
        string                                   $databaseUrl,
        private readonly string                  $s3Bucket,
        private readonly PythonServiceController $pythonServiceController,
        private readonly string                  $admin_tg,
        private readonly LoggerInterface         $logger
    )
    {
        $parts = parse_url($databaseUrl);

        if (!$parts || $parts['scheme'] !== 'mysql') {
            throw new \InvalidArgumentException('Invalid DATABASE_URL');
        }

        $this->dbHost = $parts['host'] ?? '';
        $this->dbUser = $parts['user'] ?? '';
        $this->dbPass = $parts['pass'] ?? '';
        $this->dbName = ltrim($parts['path'] ?? '', '/');

        $this->logger->error('DB Dump Params', [
            'host' => $this->dbHost,
            'user' => $this->dbUser,
            'db' => $this->dbName
        ]);
    }

    public function run(): void
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('-1 day'))->format('Y-m-d');
        $elevenDaysAgo = (new \DateTimeImmutable('-11 days'))->format('Y-m-d');

        $todayFile = "/tmp/db_backup_{$today}.sql.gz";
        $yesterdayFile = "/tmp/db_backup_{$yesterday}.sql.gz";
        $oldS3FileKey = 'db_backup_' . $elevenDaysAgo . '.sql.gz';

        // Don't overwrite today's file
        if (file_exists($todayFile)) {
            return;
        }

        // Try to create backup
        if ($this->createBackup($todayFile)) {

            if (file_exists($yesterdayFile)) {
                if (filesize($todayFile) >= filesize($yesterdayFile)) {
                    unlink($yesterdayFile);
                    $this->uploadToS3($todayFile);
                } else {
                    $this->notifyAdminSmallerBackup($todayFile, $yesterdayFile);
                }
            } else {
                // No yesterday file — first-time or new backup flow
                $this->uploadToS3($todayFile);
            }

            // Attempt to delete old backup from S3
            $this->deleteOldBackupFromS3($oldS3FileKey);
        } else {
            $this->logger->error("Backup creation failed: $todayFile");
        }
    }

    private function createBackup(string $filename): bool
    {
        $cmd = sprintf(
            '/usr/bin/mysqldump -h %s -u %s -p%s %s | gzip > %s',
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbPass),
            escapeshellarg($this->dbName),
            escapeshellarg($filename)
        );

        exec($cmd, $output, $status);

        if ($status !== 0) {
        $this->logger->error('Backup command failed', [
            'command' => $cmd,
            'output' => implode("\n", $output),
            'status' => $status
        ]);
    }
        return $status === 0;
    }

    private function uploadToS3(string $filename): void
    {
        $cmd = sprintf(
            '/usr/bin/aws s3 cp %s %s',
            escapeshellarg($filename),
            escapeshellarg($this->s3Bucket . '/' . basename($filename))
        );

        exec($cmd, $output, $status);

        if ($status !== 0) {
            $this->logger->error('S3 upload failed: ' . implode("\n", $output));
        }
//        else {
//            $this->logger->info('Backup uploaded to S3: ' . basename($filename));
//        }
    }

    private function notifyAdminSmallerBackup(string $todayFile, string $yesterdayFile): void
    {
        $message = sprintf(
            "Today's backup (%d bytes) is smaller than yesterday's (%d bytes). Manual review needed.",
            filesize($todayFile),
            filesize($yesterdayFile)
        );

        try {
            $this->pythonServiceController->callPythonService([$this->admin_tg], $message);
        } catch (\Exception $e) {
            $this->logger->error('Failed to notify admin', [
                'Exception' => $e,
                'Message' => $message,
            ]);
        }
    }

    private function deleteOldBackupFromS3(string $key): void
    {
        $cmd = sprintf(
            '/usr/bin/aws s3 rm %s',
            escapeshellarg($this->s3Bucket . '/' . $key)
        );

        exec($cmd, $output, $status);

//        if ($status === 0) {
//            $this->logger->info("Old backup deleted from S3: $key");
//        } else {
//            $this->logger->warning("Failed to delete old backup from S3: $key", [
//                'output' => implode("\n", $output)
//            ]);
//        }
    }
}
