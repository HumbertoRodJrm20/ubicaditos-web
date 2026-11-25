
# --------------------------------------------------
# PHP base: extensions shared by build and runtime
# --------------------------------------------------
FROM php:8.4-fpm-bookworm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        sockets \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# --------------------------------------------------
# Composer dependencies
# --------------------------------------------------
FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative \
    --no-scripts \
    && php artisan package:discover --ansi

# --------------------------------------------------
# Frontend assets
# --------------------------------------------------
FROM oven/bun:1.3.5 AS assets

USER root
WORKDIR /app

COPY package.json bun.lock ./

RUN bun install --frozen-lockfile

COPY . .

RUN bun run build

# --------------------------------------------------
# Production runtime
# --------------------------------------------------
FROM php-base AS production

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

WORKDIR /var/www

COPY --from=vendor --chown=www-data:www-data \
    /var/www /var/www

COPY --from=assets --chown=www-data:www-data \
    /app/public/build /var/www/public/build

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data \
        storage bootstrap/cache \
    && chmod -R ug+rwX \
        storage bootstrap/cache

# PHP-FPM listens internally on port 9000.
EXPOSE 9000

CMD ["php-fpm"]
