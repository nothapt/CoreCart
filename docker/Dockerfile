FROM php:8.4-fpm-alpine

# Install PHP extensions needed by CoreCart
RUN docker-php-ext-install pdo pdo_mysql mbstring curl intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/corecart

# Install dependencies (if composer.json exists)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts 2>/dev/null || true

# Set working directory
WORKDIR /var/www/corecart
