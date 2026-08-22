<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Database Backup Command
 *
 * Per 13_INFRASTRUCTURE_AND_DEPLOYMENT.md §8:
 * Automated daily MySQL dump, stored off the primary server,
 * retained on a simple tiered schedule (daily for 30 days, monthly for a year).
 *
 * Usage: php artisan backup:database [--tiered]
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--tiered : Apply tiered retention policy}';
    protected $description = 'Create a MySQL database backup with optional tiered retention';

    public function handle(): int
    {
        $this->info('Starting database backup...');

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $dayOfMonth = now()->day;
        $isMonthly = $dayOfMonth === 1;

        // Determine backup directory based on tiered retention
        $tier = $isMonthly ? 'monthly' : 'daily';
        $backupDir = "backups/{$tier}";
        $filename = "{$dbName}_{$timestamp}.sql.gz";
        $localPath = storage_path("app/{$backupDir}/{$filename}");

        // Ensure directory exists
        $dir = storage_path("app/{$backupDir}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Build mysqldump command
        $passwordArg = $dbPass ? "-p'{$dbPass}'" : '';
        $dumpCommand = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s --single-transaction --routines --triggers | gzip > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            $passwordArg,
            escapeshellarg($dbName),
            escapeshellarg($localPath)
        );

        $this->info("Dumping database '{$dbName}'...");

        exec($dumpCommand, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Database backup failed!');
            $this->error(implode("\n", $output));
            return self::FAILURE;
        }

        $fileSize = filesize($localPath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        $this->info("✅ Backup created: {$localPath} ({$fileSizeMB} MB)");

        // Apply tiered retention if requested
        if ($this->option('tiered')) {
            $this->applyRetention($backupDir, $tier);
        }

        // Log the backup
        $this->logBackup($filename, $tier, $fileSize);

        return self::SUCCESS;
    }

    /**
     * Apply tiered retention policy
     * - Daily: keep last 30 days
     * - Monthly: keep last 12 months
     */
    private function applyRetention(string $backupDir, string $tier): void
    {
        $retentionDays = $tier === 'monthly' ? 365 : 30;
        $cutoff = now()->subDays($retentionDays);

        $dir = storage_path("app/{$backupDir}");
        $files = glob("{$dir}/*.sql.gz");

        $deleted = 0;
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime < $cutoff->timestamp) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Retention policy: deleted {$deleted} backups older than {$retentionDays} days.");
        }
    }

    /**
     * Log the backup to a simple log file
     */
    private function logBackup(string $filename, string $tier, int $size): void
    {
        $logPath = storage_path('app/backups/backup_log.txt');
        $entry = sprintf(
            "[%s] tier=%s file=%s size=%d bytes\n",
            now()->toDateTimeString(),
            $tier,
            $filename,
            $size
        );

        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($logPath, $entry, FILE_APPEND);
    }
}
