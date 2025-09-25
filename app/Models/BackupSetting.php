<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'interval_hours',
        'retention_days',
        'backup_path',
        'last_backup_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_backup_at' => 'datetime',
    ];

    /**
     * Получить настройки резервного копирования (синглтон)
     */
    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'enabled' => true,
            'interval_hours' => 24,
            'retention_days' => 30,
            'backup_path' => '/var/backups/ftr',
        ]);
    }

    /**
     * Проверить, нужно ли создавать резервную копию
     */
    public function shouldCreateBackup(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->last_backup_at) {
            return true;
        }

        return $this->last_backup_at->addHours($this->interval_hours)->isPast();
    }

    /**
     * Обновить время последней резервной копии
     */
    public function updateLastBackupTime(): void
    {
        $this->update(['last_backup_at' => now()]);
    }
}
