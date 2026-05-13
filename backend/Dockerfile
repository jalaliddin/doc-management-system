FROM php:8.3-fpm-alpine

# Tizim kutubxonalari
RUN apk add --no-cache \
    mysql-client \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

# PHP kengaytmalari
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer fayllarini oldin nusxalash (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev --prefer-dist

# Qolgan fayllarni nusxalash
COPY . .

# Composer autoloader ni optimallashtirish
RUN composer dump-autoload --optimize --no-dev

# Papka ruxsatlari
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Entrypoint
RUN chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
