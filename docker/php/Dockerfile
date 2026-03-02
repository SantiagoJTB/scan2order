# use the non-alpine php image which already exists locally
FROM php:8.2-fpm

# install system dependencies and PHP extensions for Laravel + PostgreSQL
# this image is Debian-based so use apt-get instead of apk
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libpq-dev \
        unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# copy composer binary from an image we already have locally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# default command
CMD ["php-fpm"]
