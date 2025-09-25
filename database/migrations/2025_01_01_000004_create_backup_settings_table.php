<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->integer('interval_hours')->default(24);
            $table->integer('retention_days')->default(30);
            $table->string('backup_path', 500)->default('/var/backups/ftr');
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamps();
        });
        
        // Добавляем начальные настройки
        DB::table('backup_settings')->insert([
            'enabled' => true,
            'interval_hours' => 24,
            'retention_days' => 30,
            'backup_path' => '/var/backups/ftr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
