#!/bin/bash

# Простой скрипт полной переустановки системы ФТР
# Удаляет ВСЁ и устанавливает заново

set -e

echo "🔥 ПОЛНАЯ ПЕРЕУСТАНОВКА СИСТЕМЫ ФТР"
echo "⚠️  ВНИМАНИЕ: ВСЕ ДАННЫЕ БУДУТ УДАЛЕНЫ!"

# Проверка root
if [[ $EUID -ne 0 ]]; then
   echo "❌ Запустите с sudo"
   exit 1
fi

# Остановка сервисов
echo "🛑 Останавливаем сервисы..."
systemctl stop nginx 2>/dev/null || true
systemctl stop php8.3-fpm 2>/dev/null || true
systemctl stop mysql 2>/dev/null || true

# Удаление старого проекта
echo "🗑️  Удаляем старый проект..."
rm -rf /var/www/ftr
rm -rf /var/backups/ftr
rm -f /etc/nginx/sites-enabled/ftr
rm -f /etc/nginx/sites-available/ftr
rm -f /etc/cron.d/ftr-backup

# Удаление проблемных репозиториев
echo "🧹 Очищаем проблемные репозитории..."
rm -f /etc/apt/sources.list.d/sury-php.list
rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*.list
rm -f /etc/apt/sources.list.d/ondrej-*.list
rm -f /etc/apt/sources.list.d/*sury*.list
rm -f /etc/apt/sources.list.d/*ondrej*.list
rm -f /etc/apt/sources.list.d/php.list
# Удаляем из основного файла sources.list
sed -i '/packages.sury.org/d' /etc/apt/sources.list
sed -i '/ondrej/d' /etc/apt/sources.list
# Удаляем все ключи
apt-key del 4F4EA0AAE5267A6C 2>/dev/null || true
apt-key del 14AA40EC0831756756D7F66C4F4EA0AAE5267A6C 2>/dev/null || true
# Очищаем кэш apt
rm -rf /var/lib/apt/lists/*
rm -rf /var/cache/apt/archives/*

# Установка python3-apt для исправления ошибки apt_pkg
echo "🐍 Устанавливаем python3-apt..."
apt install -y python3-apt || true

# Обновление системы
echo "📦 Обновляем систему..."
apt update -y || true
apt upgrade -y || true

# Установка пакетов
echo "📦 Устанавливаем пакеты..."
apt install -y \
    php \
    php-fpm \
    php-mysql \
    php-xml \
    php-gd \
    php-curl \
    php-zip \
    php-mbstring \
    php-bcmath \
    php-intl \
    php-sqlite3 \
    nginx \
    mysql-server \
    composer \
    git \
    unzip \
    curl \
    cron \
    rsync

# Запуск MySQL
echo "🗄️  Запускаем MySQL..."
systemctl start mysql
systemctl enable mysql

# Настройка MySQL
echo "🗄️  Настраиваем MySQL..."
mysql_secure_installation <<EOF

y
root_password
root_password
y
y
y
y
EOF

# Создание базы данных
echo "🗄️  Создаем базу данных..."
mysql -u root -proot_password <<EOF
DROP DATABASE IF EXISTS ftr_registration;
CREATE DATABASE ftr_registration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS 'ftr_user'@'localhost';
CREATE USER 'ftr_user'@'localhost' IDENTIFIED BY 'ftr_password';
GRANT ALL PRIVILEGES ON ftr_registration.* TO 'ftr_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Создание директории проекта
echo "📁 Создаем проект..."
mkdir -p /var/www/ftr
cd /var/www/ftr

# Клонирование репозитория
echo "📥 Клонируем репозиторий..."
git clone https://github.com/punk03/ftr.git .

# Установка зависимостей
echo "📦 Устанавливаем зависимости..."

# Проверяем наличие artisan файла
if [ ! -f "artisan" ]; then
    echo "⚠️  Файл artisan не найден, создаем временный Laravel каркас..."
    
    # Создаем временный Laravel проект с совместимой версией
    cd /tmp
    composer create-project laravel/laravel:^10.0 laravel_skeleton_temp --no-interaction --prefer-dist
    cd laravel_skeleton_temp
    
    # Копируем необходимые файлы Laravel
    cp artisan /var/www/ftr/ 2>/dev/null || echo "artisan не найден в временном проекте"
    cp -r bootstrap /var/www/ftr/ 2>/dev/null || echo "bootstrap не найден"
    cp -r public /var/www/ftr/ 2>/dev/null || echo "public не найден"
    cp -r config /var/www/ftr/ 2>/dev/null || echo "config не найден"
    
    # Проверяем наличие server.php
    if [ -f "server.php" ]; then
        cp server.php /var/www/ftr/ 2>/dev/null || echo "server.php не найден"
    fi
    
    # Создаем необходимые директории Laravel в основном проекте
    mkdir -p /var/www/ftr/storage/app/public
    mkdir -p /var/www/ftr/storage/framework/cache
    mkdir -p /var/www/ftr/storage/framework/sessions
    mkdir -p /var/www/ftr/storage/framework/views
    mkdir -p /var/www/ftr/storage/logs
    mkdir -p /var/www/ftr/bootstrap/cache
    
    # Исправляем bootstrap/app.php для Laravel 10
    cd /var/www/ftr
    cat > bootstrap/app.php << 'EOF'
<?php

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

return $app;
EOF

    # Создаем недостающие классы Laravel
    mkdir -p app/Http app/Console app/Exceptions
    
    # App\Http\Kernel
    cat > app/Http/Kernel.php << 'EOF'
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
EOF

    # App\Console\Kernel
    cat > app/Console/Kernel.php << 'EOF'
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
EOF

    # App\Exceptions\Handler
    cat > app/Exceptions/Handler.php << 'EOF'
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    }
EOF

    # Создаем недостающие middleware
    mkdir -p app/Http/Middleware
    
    cat > app/Http/Middleware/RedirectIfAuthenticated.php << 'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect('/home');
            }
        }

        return $next($request);
    }
}
EOF

    cat > app/Http/Middleware/ValidateSignature.php << 'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature as Middleware;
use Symfony\Component\HttpFoundation\Response;

class ValidateSignature extends Middleware
{
    protected $except = [
        //
    ];
    }
EOF

    # Создаем недостающие провайдеры
    mkdir -p app/Providers
    
    cat > app/Providers/AppServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
EOF

    cat > app/Providers/AuthServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        //
    }
}
EOF

    cat > app/Providers/EventServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
EOF

    cat > app/Providers/RouteServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user() ? $request->user()->id : $request->ip());
        });
    }
}
EOF

    # Создаем недостающие файлы маршрутов
    mkdir -p routes
    cat > routes/api.php << 'EOF'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
EOF

    cat > routes/console.php << 'EOF'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
EOF

    cat > routes/web.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
EOF

    # Создаем базовую view
    mkdir -p resources/views
    cat > resources/views/welcome.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FTR Registration System</title>
</head>
<body>
    <h1>FTR Registration System</h1>
    <p>System is running successfully!</p>
</body>
</html>
EOF

    # Создаем config/app.php с провайдерами
    cat > config/app.php << 'EOF'
<?php

return [
    'name' => env('APP_NAME', 'FTR Registration'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => [
        'driver' => 'file',
    ],
    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'Date' => Illuminate\Support\Facades\Date::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Js' => Illuminate\Support\Js::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Process' => Illuminate\Support\Facades\Process::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'RateLimiter' => Illuminate\Support\Facades\RateLimiter::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Vite' => Illuminate\Support\Facades\Vite::class,
    ],
];
EOF
    
    # Очищаем временный проект
    cd /var/www/ftr
    rm -rf /tmp/laravel_skeleton_temp
fi

composer install --no-dev --optimize-autoloader

# Настройка прав
echo "🔐 Настраиваем права..."
chown -R www-data:www-data /var/www/ftr
chmod -R 755 /var/www/ftr
chmod -R 775 /var/www/ftr/storage /var/www/ftr/bootstrap/cache

# Создание .env
echo "⚙️  Настраиваем окружение..."
if [ -f ".env.example" ]; then
    cp .env.example .env
else
    # Создаем базовый .env файл
    cat > .env << 'EOF'
APP_NAME="FTR Registration"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ftr_registration
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF
fi
sed -i 's/DB_DATABASE=.*/DB_DATABASE=ftr_registration/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=ftr_user/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=ftr_password/' .env
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env

# Генерация ключа
echo "🔑 Генерируем ключ..."
if [ -f "artisan" ]; then
    php artisan key:generate
else
    echo "⚠️  Файл artisan не найден, пропускаем генерацию ключа"
fi

# Импорт существующей базы данных
echo "📥 Импортируем существующую базу данных..."
if [ -f "reg_danceorg.sql" ]; then
    mysql -u ftr_user -pftr_password ftr_registration < reg_danceorg.sql
    echo "✅ База данных импортирована"
else
    echo "⚠️  Файл reg_danceorg.sql не найден, пропускаем импорт"
fi

# Запуск миграций
echo "🗄️  Запускаем миграции..."
if [ -f "artisan" ]; then
    php artisan migrate --force
else
    echo "⚠️  Файл artisan не найден, пропускаем миграции"
fi

# Создание директории бэкапов
echo "💾 Создаем директорию бэкапов..."
mkdir -p /var/backups/ftr
chown -R www-data:www-data /var/backups/ftr

# Определяем версию PHP
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "🔍 Обнаружена версия PHP: $PHP_VERSION"

# Настройка Nginx
echo "🌐 Настраиваем Nginx..."
cat > /etc/nginx/sites-available/ftr <<EOF
server {
    listen 80;
    server_name _;
    root /var/www/ftr/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

# Активация сайта
ln -sf /etc/nginx/sites-available/ftr /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Настройка cron
echo "⏰ Настраиваем cron..."
cat > /etc/cron.d/ftr-backup <<EOF
0 2 * * * root /var/www/ftr/backup.sh
EOF

# Создание скрипта обновления
echo "🔄 Создаем скрипт обновления..."
cat > /var/www/ftr/update.sh <<EOF
#!/bin/bash
cd /var/www/ftr
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
systemctl reload php${PHP_VERSION}-fpm
systemctl reload nginx
echo "✅ Обновление завершено"
EOF

chmod +x /var/www/ftr/update.sh

# Создание скрипта бэкапа
echo "💾 Создаем скрипт бэкапа..."
cat > /var/www/ftr/backup.sh <<EOF
#!/bin/bash
BACKUP_DIR="/var/backups/ftr"
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="ftr_backup_\$DATE.sql"

mysqldump -u ftr_user -pftr_password ftr_registration > \$BACKUP_DIR/\$BACKUP_FILE
gzip \$BACKUP_DIR/\$BACKUP_FILE
find \$BACKUP_DIR -name "ftr_backup_*.sql.gz" -mtime +30 -delete

echo "✅ Бэкап создан: \$BACKUP_FILE.gz"
EOF

chmod +x /var/www/ftr/backup.sh

# Перезапуск сервисов
echo "🔄 Перезапускаем сервисы..."
systemctl restart php${PHP_VERSION}-fpm
systemctl restart nginx
systemctl restart mysql

# Проверка конфигурации Nginx
echo "🔍 Проверяем конфигурацию Nginx..."
nginx -t
if [ $? -ne 0 ]; then
    echo "❌ Ошибка в конфигурации Nginx"
    exit 1
fi

# Проверка статуса
echo "✅ Проверяем статус..."
systemctl is-active --quiet nginx && echo "✅ Nginx работает" || echo "❌ Nginx не работает"
systemctl is-active --quiet php${PHP_VERSION}-fpm && echo "✅ PHP-FPM работает" || echo "❌ PHP-FPM не работает"
systemctl is-active --quiet mysql && echo "✅ MySQL работает" || echo "❌ MySQL не работает"

# Проверка сайта
echo "🌐 Проверяем сайт..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|404"; then
    echo "✅ Сайт доступен"
else
    echo "❌ Сайт недоступен"
    echo "🔍 Диагностика проблем:"
    echo "📋 Проверяем логи Nginx..."
    tail -5 /var/log/nginx/error.log 2>/dev/null || echo "Логи Nginx недоступны"
    echo "📋 Проверяем логи PHP-FPM..."
    tail -5 /var/log/php${PHP_VERSION}-fpm.log 2>/dev/null || echo "Логи PHP-FPM недоступны"
    echo "📋 Проверяем права доступа..."
    ls -la /var/www/ftr/public/ 2>/dev/null || echo "Директория public недоступна"
    echo "📋 Проверяем файл index.php..."
    ls -la /var/www/ftr/public/index.php 2>/dev/null || echo "Файл index.php не найден"
fi

echo ""
echo "🎉 УСТАНОВКА ЗАВЕРШЕНА!"
echo "🌐 Сайт: http://$(hostname -I | awk '{print $1}')"
echo "📁 Проект: /var/www/ftr"
echo "🔄 Обновление: /var/www/ftr/update.sh"
echo "💾 Бэкап: /var/www/ftr/backup.sh"
echo ""
echo "⚠️  НЕ ЗАБУДЬТЕ:"
echo "   1. Настроить SSL"
echo "   2. Изменить пароли"
echo "   3. Создать администратора"
echo ""
