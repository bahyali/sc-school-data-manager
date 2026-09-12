<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database';
    protected $description = 'Backup the main database (keep 5) and extra databases (keep 1)';

    public function handle()
    {
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port');

        $databases = $this->databasesToBackup();
        $mysqldump = config('database.backup.mysqldump_path', 'mysqldump');
        $mainDatabase = config('database.connections.mysql.database');
        $keepMain = (int) config('database.backup.keep_main', 5);
        $keepExtra = (int) config('database.backup.keep_extra', 1);

        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $failed = false;

        foreach ($databases as $database) {
            $filename = $database . '_' . date('Y-m-d_H-i-s') . '.sql';
            $fullPath = $backupPath . DIRECTORY_SEPARATOR . $filename;

            $passwordPart = ($password === null || $password === '')
                ? ''
                : '-p' . escapeshellarg($password);

            $command = sprintf(
                '%s --no-tablespaces -h %s -P %s -u %s %s --result-file=%s %s',
                escapeshellarg($mysqldump),
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $passwordPart,
                escapeshellarg($fullPath),
                escapeshellarg($database)
            );

            $output = [];
            exec($command, $output, $result);

            if ($result === 0 && file_exists($fullPath) && filesize($fullPath) > 0) {
                $this->info("Backup created: {$filename}");
            } else {
                $this->error("Backup failed for database: {$database}");
                if (!empty($output)) {
                    $this->error(implode(PHP_EOL, $output));
                }
                $failed = true;
            }

            $maxBackups = ($database === $mainDatabase) ? $keepMain : $keepExtra;
            $this->pruneOldBackups($backupPath, $database, $maxBackups);
        }

        return $failed ? 1 : 0;
    }

    protected function databasesToBackup()
    {
        $extra = array_filter(array_map('trim', explode(
            ',',
            (string) config('database.backup.extra_databases', 'schoolcred_wp')
        )));

        return array_values(array_unique(array_filter(array_merge(
            [config('database.connections.mysql.database')],
            $extra
        ))));
    }

    protected function pruneOldBackups($backupPath, $database, $maxBackups)
    {
        $backups = glob($backupPath . DIRECTORY_SEPARATOR . $database . '_*.sql') ?: [];
        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        if (count($backups) > $maxBackups) {
            $toDelete = array_slice($backups, $maxBackups);
            foreach ($toDelete as $file) {
                unlink($file);
                $this->info("Deleted old backup: " . basename($file));
            }
        }
    }
}
