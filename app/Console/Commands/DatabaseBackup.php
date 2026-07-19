<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database zaxira nusxasini (backup) olish va eski zaxiralarni tozalash';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Database zaxira nusxasini olish boshlandi...');

        $connection = config('database.default');
        $backupDir = storage_path('app/backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (!File::exists($dbPath)) {
                $this->error("SQLite ma'lumotlar bazasi fayli topilmadi: {$dbPath}");
                return 1;
            }

            $backupFile = "{$backupDir}/blackdoor_{$timestamp}.sqlite";
            File::copy($dbPath, $backupFile);
            $this->info("SQLite zaxira nusxasi muvaffaqiyatli yaratildi: {$backupFile}");
        } elseif ($connection === 'pgsql') {
            $config = config('database.connections.pgsql');
            
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? '5432';
            $database = $config['database'] ?? 'blackdoor';
            $username = $config['username'] ?? 'blackdoor';
            $password = $config['password'] ?? '';

            $backupFile = "{$backupDir}/blackdoor_{$timestamp}.sql.gz";

            // pg_dump command with gzip compression
            // Set password environment variable for pg_dump to read securely
            $cmd = [
                'pg_dump',
                '-h', $host,
                '-p', (string)$port,
                '-U', $username,
                '-d', $database,
                '--no-owner',
                '--no-privileges',
                '--format=plain',
            ];

            $process = new Process($cmd);
            $process->setEnv(['PGPASSWORD' => $password]);
            
            // Output through gzip
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error("pg_dump xatoligi: " . $process->getErrorOutput());
                return 1;
            }

            $sqlContent = $process->getOutput();
            $compressed = gzencode($sqlContent, 9);
            if ($compressed === false) {
                $this->error("Gzip siqish xatoligi!");
                return 1;
            }

            File::put($backupFile, $compressed);
            $this->info("PostgreSQL zaxira nusxasi muvaffaqiyatli yaratildi: {$backupFile}");
        } else {
            $this->error("Qo'llab-quvvatlanmaydigan ma'lumotlar bazasi drayveri: {$connection}");
            return 1;
        }

        // Clean up old backups (older than 7 days)
        $this->cleanupOldBackups($backupDir);

        return 0;
    }

    /**
     * Clean up backups older than 7 days.
     */
    private function cleanupOldBackups(string $backupDir): void
    {
        $this->info('7 kundan eski zaxira fayllarni tozalash...');
        $files = File::files($backupDir);
        $now = time();
        $retentionSecs = 7 * 24 * 60 * 60; // 7 days
        $deletedCount = 0;

        foreach ($files as $file) {
            $mtime = $file->getMTime();
            if (($now - $mtime) > $retentionSecs) {
                File::delete($file->getRealPath());
                $deletedCount++;
            }
        }

        $this->info("Tozalash yakunlandi. {$deletedCount} ta eski fayl o'chirildi.");
    }
}
