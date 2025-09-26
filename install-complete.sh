#!/bin/bash

# Полный скрипт установки системы регистрации ФТР на Ubuntu 24.04
# Делает ВСЕ автоматически - никаких ручных команд не требуется!
# Использование: bash install-complete.sh

set -e

echo "🚀 Полная установка системы регистрации ФТР на Ubuntu 24.04..."

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для вывода сообщений
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Проверка прав root
if [[ $EUID -ne 0 ]]; then
   error "Этот скрипт должен быть запущен с правами root"
   exit 1
fi

# Обновление системы
log "Обновляем систему..."
apt update && apt upgrade -y

# Установка PHP из стандартных репозиториев Ubuntu
log "Устанавливаем PHP из стандартных репозиториев..."
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
    php-intl

# Установка остальных пакетов
log "Устанавливаем остальные пакеты..."
apt install -y \
    nginx \
    mysql-server \
    composer \
    git \
    unzip \
    curl \
    cron \
    rsync

# Настройка MySQL
log "Настраиваем MySQL..."
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
log "Создаем базу данных..."
mysql -u root -proot_password <<EOF
CREATE DATABASE IF NOT EXISTS ftr_registration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ftr_user'@'localhost' IDENTIFIED BY 'ftr_password';
GRANT ALL PRIVILEGES ON ftr_registration.* TO 'ftr_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Создание директории для проекта
PROJECT_DIR="/var/www/ftr"
log "Создаем директорию проекта: $PROJECT_DIR"
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

# Клонирование репозитория (если еще не клонирован)
if [ ! -d ".git" ]; then
    log "Клонируем репозиторий..."
    git clone https://github.com/punk03/ftr.git .
fi

# Обновление кода
log "Обновляем код из репозитория..."
git pull origin main

# Установка зависимостей PHP
log "Устанавливаем зависимости PHP..."
echo "DEBUG: Переходим в /var/www/ftr"
cd /var/www/ftr || { echo "Ошибка: не удалось перейти в /var/www/ftr"; exit 1; }
echo "DEBUG: Текущая директория: $(pwd)"
echo "DEBUG: Содержимое директории:"
ls -la

# Если отсутствует artisan или bootstrap/app.php, подтянем недостающие файлы каркаса Laravel
if [ ! -f "artisan" ] || [ ! -f "bootstrap/app.php" ]; then
  echo "DEBUG: Недостающие файлы Laravel — создаю временный каркас и копирую недостающие файлы"
  TMP_LARA="/tmp/laravel_skeleton_$$"
  rm -rf "$TMP_LARA"
  mkdir -p "$TMP_LARA"
  composer create-project --quiet --no-dev --prefer-dist laravel/laravel:^10.0 "$TMP_LARA"
  # Копируем только недостающие файлы, не перезаписывая существующие
  if [ ! -f artisan ] && [ -f "$TMP_LARA/artisan" ]; then cp "$TMP_LARA/artisan" ./; fi
  if [ ! -f server.php ] && [ -f "$TMP_LARA/server.php" ]; then cp "$TMP_LARA/server.php" ./; fi
  for d in bootstrap public config; do
    if [ ! -d "$d" ] && [ -d "$TMP_LARA/$d" ]; then
      rsync -a "$TMP_LARA/$d" ./
    fi
  done
  rm -rf "$TMP_LARA"
fi

if [ ! -f "composer.json" ]; then
    echo "ERROR: Файл composer.json не найден в директории $(pwd)"
    exit 1
fi
echo "DEBUG: Запускаем composer install"
composer install --no-dev --optimize-autoloader

# Настройка прав доступа
log "Настраиваем права доступа..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/storage $PROJECT_DIR/bootstrap/cache

# Создание файла окружения
log "Создаем файл окружения..."
if [ ! -f ".env" ]; then
    cp env.example .env
fi

# Настройка базы данных в .env
log "Настраиваем подключение к базе данных..."
sed -i 's/DB_DATABASE=.*/DB_DATABASE=ftr_registration/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=ftr_user/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=ftr_password/' .env
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env

# Генерация ключа приложения
log "Генерируем ключ приложения..."
php artisan key:generate

# Запуск миграций
log "Запускаем миграции базы данных..."
php artisan migrate --force

# Создание директории для резервных копий
log "Создаем директорию для резервных копий..."
mkdir -p /var/backups/ftr
chown -R www-data:www-data /var/backups/ftr

# Настройка Nginx
log "Настраиваем Nginx..."
cat > /etc/nginx/sites-available/ftr <<EOF
server {
    listen 80;
    server_name _;
    root $PROJECT_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Активация сайта
ln -sf /etc/nginx/sites-available/ftr /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Перезапуск Nginx
systemctl restart nginx

# Настройка cron для резервного копирования
log "Настраиваем автоматическое резервное копирование..."
cat > /etc/cron.d/ftr-backup <<EOF
# Резервное копирование ФТР каждые 6 часов
0 */6 * * * www-data /usr/bin/php $PROJECT_DIR/artisan backup:create >> /var/log/ftr-backup.log 2>&1
EOF

# Создание скрипта обновления
log "Создаем скрипт обновления..."
cat > update.sh <<EOF
#!/bin/bash
set -e

echo "🔄 Обновляем систему регистрации ФТР..."

cd $PROJECT_DIR

# Обновление кода
git pull origin main

# Обновление зависимостей
composer install --no-dev --optimize-autoloader

# Запуск миграций
php artisan migrate --force

# Очистка кэша
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Перезапуск PHP-FPM
systemctl restart php8.3-fpm

echo "✅ Обновление завершено!"
EOF

chmod +x update.sh

# Создание скрипта резервного копирования
log "Создаем скрипт резервного копирования..."
cat > backup.sh <<EOF
#!/bin/bash
set -e

BACKUP_DIR="/var/backups/ftr"
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="ftr_backup_\$DATE.sql"

echo "💾 Создаем резервную копию базы данных..."

# Создание резервной копии БД
mysqldump -u ftr_user -pftr_password ftr_registration > \$BACKUP_DIR/\$BACKUP_FILE

# Сжатие
gzip \$BACKUP_DIR/\$BACKUP_FILE

# Удаление старых копий (старше 30 дней)
find \$BACKUP_DIR -name "ftr_backup_*.sql.gz" -mtime +30 -delete

echo "✅ Резервная копия создана: \$BACKUP_FILE.gz"
EOF

chmod +x backup.sh

# Перезапуск сервисов
log "Перезапускаем сервисы..."
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl restart mysql

# Проверка статуса сервисов
log "Проверяем статус сервисов..."
systemctl is-active --quiet nginx && echo "✅ Nginx работает" || echo "❌ Nginx не работает"
systemctl is-active --quiet php8.3-fpm && echo "✅ PHP-FPM работает" || echo "❌ PHP-FPM не работает"
systemctl is-active --quiet mysql && echo "✅ MySQL работает" || echo "❌ MySQL не работает"

# Проверка версии PHP
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2)
log "Установлен PHP версии: $PHP_VERSION"

# Проверка доступности сайта
log "Проверяем доступность сайта..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|404"; then
    echo "✅ Сайт доступен"
else
    echo "❌ Сайт недоступен"
fi

log "🎉 УСТАНОВКА ПОЛНОСТЬЮ ЗАВЕРШЕНА!"
log "🌐 Сайт доступен по адресу: http://$(hostname -I | awk '{print $1}')"
log "📁 Проект находится в: $PROJECT_DIR"
log "💾 Резервные копии: /var/backups/ftr"
log "🔄 Для обновления используйте: ./update.sh"
log "💾 Для резервного копирования: ./backup.sh"

echo ""
warn "⚠️  ВАЖНО - НЕ ЗАБУДЬТЕ:"
warn "   1. Настроить SSL сертификат (Let's Encrypt)"
warn "   2. Изменить пароли по умолчанию:"
warn "      - MySQL root: root_password"
warn "      - MySQL ftr_user: ftr_password"
warn "   3. Настроить файрвол (ufw)"
warn "   4. Импортировать существующие данные в БД"
warn "   5. Создать первого администратора в системе"

echo ""
log "🔐 Для создания первого администратора выполните:"
log "   mysql -u ftr_user -pftr_password ftr_registration"
log "   UPDATE users SET role='admin' WHERE id=1;"
log "   (или создайте нового пользователя через веб-интерфейс)"

echo ""
log "📋 Следующие шаги:"
log "   1. Откройте браузер и перейдите на http://$(hostname -I | awk '{print $1}')"
log "   2. Войдите в систему с учетными данными администратора"
log "   3. Настройте первое мероприятие"
log "   4. Импортируйте существующие данные"

echo ""
log "🎊 СИСТЕМА ГОТОВА К РАБОТЕ! 🎊"
