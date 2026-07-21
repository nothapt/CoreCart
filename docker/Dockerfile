FROM php:8.4-fpm-alpine

# Install PHP extensions needed by CoreCart
RUN docker-php-ext-install pdo pdo_mysql mbstring curl intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/corecart

# Install dependencies (fail build if this fails)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

WORKDIR /var/www/corecart
