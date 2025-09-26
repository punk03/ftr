<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Проверяем существование таблицы users
        if (Schema::hasTable('users')) {
            // Проверяем существование колонки role
            if (!Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->enum('role', ['admin', 'registrar', 'statistician'])->default('registrar')->after('is_admin');
                });
                
                // Обновляем существующих админов
                if (Schema::hasColumn('users', 'is_admin')) {
                    DB::table('users')->where('is_admin', 1)->update(['role' => 'admin']);
                }
            }
        } else {
            // Создаем таблицу users если её нет
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->boolean('is_admin')->default(false);
                $table->enum('role', ['admin', 'registrar', 'statistician'])->default('registrar');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
