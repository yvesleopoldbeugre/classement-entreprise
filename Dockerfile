# Image applicative : PHP 8.4-FPM (Alpine) + extensions Laravel + Composer + Node (Vite)
# Alpine est choisi pour son empreinte disque réduite (apk plus léger et robuste qu'apt).
FROM php:8.4-fpm-alpine

# Dépendances système + extensions PHP
RUN apk add --no-cache \
        git \
        curl \
        unzip \
        icu-dev \
        libzip-dev \
        libpng-dev \
        oniguruma-dev \
        mysql-client \
        nodejs \
        npm \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Composer (copié depuis l'image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Config PHP + point d'entrée
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
