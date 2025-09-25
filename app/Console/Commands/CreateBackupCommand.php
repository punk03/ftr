<?php

namespace App\Console\Commands;

use App\Models\BackupSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать резервную копию базы данных';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = BackupSetting::getSettings();
        
        if (!$settings->enabled) {
            $this->info('Резервное копирование отключено в настройках.');
            return;
        }
        
        if (!$settings->shouldCreateBackup()) {
            $this->info('Резервное копирование не требуется в данный момент.');
            return;
        }
        
        $this->info('Создаем резервную копию базы данных...');
        
        try {
            $backupPath = $settings->backup_path;
            
            // Создаем директорию если не существует
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $date = now()->format('Y-m-d_H-i-s');
            $filename = "ftr_backup_{$date}.sql";
            $filepath = "{$backupPath}/{$filename}";
            
            // Получаем настройки БД
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            // Создаем резервную копию
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception('Ошибка при создании резервной копии');
            }
            
            // Сжимаем файл
            $compressedFile = "{$filepath}.gz";
            exec("gzip {$filepath}");
            
            // Удаляем старые копии
            $this->cleanOldBackups($backupPath, $settings->retention_days);
            
            // Обновляем время последней резервной копии
            $settings->updateLastBackupTime();
            
            $this->info("✅ Резервная копия создана: {$filename}.gz");
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при создании резервной копии: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Удалить старые резервные копии
     */
    private function cleanOldBackups(string $backupPath, int $retentionDays): void
    {
        $files = glob("{$backupPath}/ftr_backup_*.sql.gz");
        
        foreach ($files as $file) {
            if (filemtime($file) < strtotime("-{$retentionDays} days")) {
                unlink($file);
                $this->info("Удалена старая резервная копия: " . basename($file));
            }
        }
    }
}
