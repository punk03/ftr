# Инструкция по установке системы регистрации ФТР

## Системные требования

- **ОС**: Ubuntu 24.04 LTS
- **PHP**: 8.1 или выше
- **MySQL**: 8.0 или выше
- **Nginx**: 1.18 или выше
- **Composer**: 2.0 или выше
- **Git**: 2.0 или выше

## Автоматическая установка

### 1. Подготовка сервера

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка необходимых пакетов
sudo apt install -y curl wget git unzip
```

### 2. Запуск скрипта установки

```bash
# Скачивание и запуск скрипта установки
wget https://raw.githubusercontent.com/punk03/ftr/master/install.sh
chmod +x install.sh
sudo ./install.sh
```

Скрипт автоматически:
- Установит все необходимые пакеты
- Настроит MySQL и создаст базу данных
- Настроит Nginx и PHP-FPM
- Установит зависимости проекта
- Настроит автоматическое резервное копирование
- Создаст скрипты для обновления

### 3. Настройка базы данных

После установки необходимо импортировать существующие данные:

```bash
# Подключение к MySQL
mysql -u ftr_user -pftr_password ftr_registration

# Импорт существующих данных (замените путь на актуальный)
source /path/to/reg_danceorg.sql;
```

## Ручная установка

### 1. Установка зависимостей

```bash
# PHP и расширения
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-gd php8.1-curl php8.1-zip php8.1-mbstring php8.1-bcmath php8.1-intl

# MySQL
sudo apt install -y mysql-server

# Nginx
sudo apt install -y nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Настройка MySQL

```bash
# Безопасная настройка MySQL
sudo mysql_secure_installation

# Создание базы данных
mysql -u root -p
```

```sql
CREATE DATABASE ftr_registration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ftr_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON ftr_registration.* TO 'ftr_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Клонирование проекта

```bash
# Создание директории
sudo mkdir -p /var/www/ftr
cd /var/www/ftr

# Клонирование репозитория
sudo git clone https://github.com/punk03/ftr.git .

# Установка зависимостей
sudo composer install --no-dev --optimize-autoloader
```

### 4. Настройка окружения

```bash
# Копирование файла окружения
sudo cp env.example .env

# Генерация ключа приложения
sudo php artisan key:generate

# Настройка прав доступа
sudo chown -R www-data:www-data /var/www/ftr
sudo chmod -R 755 /var/www/ftr
sudo chmod -R 775 storage bootstrap/cache
```

### 5. Настройка .env

Отредактируйте файл `.env`:

```env
APP_NAME="ФТР Регистрация"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ftr_registration
DB_USERNAME=ftr_user
DB_PASSWORD=strong_password

# Настройки резервного копирования
BACKUP_ENABLED=true
BACKUP_INTERVAL_HOURS=24
BACKUP_RETENTION_DAYS=30
BACKUP_PATH=/var/backups/ftr
```

### 6. Запуск миграций

```bash
sudo php artisan migrate --force
```

### 7. Настройка Nginx

Создайте файл конфигурации `/etc/nginx/sites-available/ftr`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/ftr/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Активируйте сайт:

```bash
sudo ln -s /etc/nginx/sites-available/ftr /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### 8. Настройка автоматического резервного копирования

```bash
# Создание директории для резервных копий
sudo mkdir -p /var/backups/ftr
sudo chown -R www-data:www-data /var/backups/ftr

# Добавление задачи в cron
echo "0 */6 * * * www-data /usr/bin/php /var/www/ftr/artisan backup:create >> /var/log/ftr-backup.log 2>&1" | sudo tee /etc/cron.d/ftr-backup
```

## Обновление системы

Для обновления системы используйте скрипт:

```bash
cd /var/www/ftr
sudo ./update.sh
```

Или вручную:

```bash
cd /var/www/ftr
sudo git pull origin master
sudo composer install --no-dev --optimize-autoloader
sudo php artisan migrate --force
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache
sudo systemctl restart php8.1-fpm
```

## Создание резервной копии

```bash
cd /var/www/ftr
sudo ./backup.sh
```

Или через Artisan:

```bash
sudo php artisan backup:create
```

## Настройка SSL (Let's Encrypt)

```bash
# Установка Certbot
sudo apt install -y certbot python3-certbot-nginx

# Получение сертификата
sudo certbot --nginx -d your-domain.com

# Автоматическое обновление
sudo crontab -e
# Добавьте строку:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

## Мониторинг и логи

### Логи приложения
```bash
tail -f /var/www/ftr/storage/logs/laravel.log
```

### Логи Nginx
```bash
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Логи резервного копирования
```bash
tail -f /var/log/ftr-backup.log
```

## Устранение неполадок

### Проблемы с правами доступа
```bash
sudo chown -R www-data:www-data /var/www/ftr
sudo chmod -R 755 /var/www/ftr
sudo chmod -R 775 storage bootstrap/cache
```

### Проблемы с кэшем
```bash
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear
```

### Проблемы с базой данных
```bash
sudo php artisan migrate:status
sudo php artisan migrate --force
```

### Проверка статуса сервисов
```bash
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
```

## Безопасность

1. **Измените пароли по умолчанию**
2. **Настройте файрвол**:
   ```bash
   sudo ufw enable
   sudo ufw allow 22
   sudo ufw allow 80
   sudo ufw allow 443
   ```
3. **Регулярно обновляйте систему**
4. **Настройте мониторинг**
5. **Делайте регулярные резервные копии**

## Поддержка

При возникновении проблем:
1. Проверьте логи системы
2. Убедитесь в правильности настроек
3. Создайте Issue в репозитории GitHub
4. Обратитесь к администратору системы
