FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    mysql-client libzip-dev zip unzip git curl \
    oniguruma-dev freetype-dev libjpeg-turbo-dev libpng-dev

RUN docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

CMD ["php-fpm"]
