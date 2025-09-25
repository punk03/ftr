<?php

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Главная страница
Route::get('/', function () {
    return redirect()->route('login');
});

// Авторизация
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Защищенные маршруты
Route::middleware(['auth'])->group(function () {
    
    // Дашборд для разных ролей
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'statistician':
                return redirect()->route('statistics.dashboard');
            default:
                return redirect()->route('registrations.create');
        }
    })->name('dashboard');
    
    // Регистрация (доступна админам и регистраторам)
    Route::middleware(['role:admin,registrar'])->group(function () {
        Route::get('/registrations/create', [RegistrationController::class, 'create'])->name('registrations.create');
        Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
        Route::get('/registrations/{group}', [RegistrationController::class, 'show'])->name('registrations.show');
        
        // API для автозаполнения и расчета стоимости
        Route::get('/api/autocomplete', [RegistrationController::class, 'autocomplete'])->name('api.autocomplete');
        Route::post('/api/calculate-cost', [RegistrationController::class, 'calculateCost'])->name('api.calculate-cost');
    });
    
    // Админ-панель (только для админов)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Управление мероприятиями
        Route::get('/events', [AdminController::class, 'events'])->name('events');
        Route::get('/events/create', [AdminController::class, 'eventForm'])->name('events.create');
        Route::post('/events', [AdminController::class, 'eventForm'])->name('events.store');
        Route::get('/events/{event}/edit', [AdminController::class, 'eventForm'])->name('events.edit');
        Route::post('/events/{event}', [AdminController::class, 'eventForm'])->name('events.update');
        
        // Управление пользователями
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::post('/users/{user}', [AdminController::class, 'editUser'])->name('users.update');
        
        // Настройки резервного копирования
        Route::get('/backup-settings', [AdminController::class, 'backupSettings'])->name('backup-settings');
        Route::post('/backup-settings', [AdminController::class, 'backupSettings'])->name('backup-settings.update');
        Route::post('/backup/create', [AdminController::class, 'createBackup'])->name('backup.create');
        
        // Все регистрации
        Route::get('/registrations', [AdminController::class, 'registrations'])->name('registrations');
        
        // Статистика
        Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
    });
    
    // Статистика (доступна админам и статистам)
    Route::middleware(['role:admin,statistician'])->prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/dashboard', [StatisticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/events/{event}', [StatisticsController::class, 'eventStatistics'])->name('events.show');
        Route::get('/events/{event}/export', [StatisticsController::class, 'exportEventStatistics'])->name('events.export');
        Route::get('/financial', [StatisticsController::class, 'financialReport'])->name('financial');
        Route::get('/nominations', [StatisticsController::class, 'nominationReport'])->name('nominations');
        Route::get('/disciplines', [StatisticsController::class, 'disciplineReport'])->name('disciplines');
    });
});
