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
apt install -y python3-apt

# Обновление системы
echo "📦 Обновляем систему..."
apt update -y
apt upgrade -y

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
    nginx \
    mysql-server \
    composer \
    git \
    unzip \
    curl \
    cron \
    rsync

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
composer install --no-dev --optimize-autoloader

# Настройка прав
echo "🔐 Настраиваем права..."
chown -R www-data:www-data /var/www/ftr
chmod -R 755 /var/www/ftr
chmod -R 775 /var/www/ftr/storage /var/www/ftr/bootstrap/cache

# Создание .env
echo "⚙️  Настраиваем окружение..."
cp .env.example .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=ftr_registration/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=ftr_user/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=ftr_password/' .env
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env

# Генерация ключа
echo "🔑 Генерируем ключ..."
php artisan key:generate

# Запуск миграций
echo "🗄️  Запускаем миграции..."
php artisan migrate --force

# Создание директории бэкапов
echo "💾 Создаем директорию бэкапов..."
mkdir -p /var/backups/ftr
chown -R www-data:www-data /var/backups/ftr

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
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
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
systemctl reload php8.3-fpm
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
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl restart mysql

# Проверка статуса
echo "✅ Проверяем статус..."
systemctl is-active --quiet nginx && echo "✅ Nginx работает" || echo "❌ Nginx не работает"
systemctl is-active --quiet php8.3-fpm && echo "✅ PHP-FPM работает" || echo "❌ PHP-FPM не работает"
systemctl is-active --quiet mysql && echo "✅ MySQL работает" || echo "❌ MySQL не работает"

# Проверка сайта
echo "🌐 Проверяем сайт..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|404"; then
    echo "✅ Сайт доступен"
else
    echo "❌ Сайт недоступен"
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
