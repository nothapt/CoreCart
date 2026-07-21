FROM php:8.4-fpm-alpine

# Install PHP extensions needed by CoreCart
RUN docker-php-ext-install pdo pdo_mysql mbstring curl intl

# Set working directory
WORKDIR /var/www/corecart
