#!/bin/sh
set -e

echo "==> Laravel ishga tushirilmoqda..."

# Storage papkalarini yaratish
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

# Storage link
php artisan storage:link 2>/dev/null || true

exec php-fpm
