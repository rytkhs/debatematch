FROM php:8.4.8RC1-fpm-alpine AS base

ENV PHP_INI_DIR /usr/local/etc/php
ENV TZ Asia/Tokyo

RUN apk update && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    build-base \
    autoconf \
    libzip-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    git \
    openssh-client \
    nodejs \
    npm \
    && apk add --no-cache \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    oniguruma \
    mysql-client \
    supervisor \
    tzdata

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions exif gd intl mbstring pdo_mysql sockets zip redis pcntl

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/memory_limit = .*/memory_limit = 256M/' $PHP_INI_DIR/php.ini \
    && sed -i 's/post_max_size = .*/post_max_size = 50M/' $PHP_INI_DIR/php.ini \
    && sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' $PHP_INI_DIR/php.ini \
    && sed -i 's/;opcache.enable=.*/opcache.enable=1/' $PHP_INI_DIR/php.ini \
    && sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=128/' $PHP_INI_DIR/php.ini \
    && sed -i 's/;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=10000/' $PHP_INI_DIR/php.ini \
    && sed -i 's/;opcache.revalidate_freq=.*/opcache.revalidate_freq=0/' $PHP_INI_DIR/php.ini \
    && sed -i 's/;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' $PHP_INI_DIR/php.ini \
    && sed -i 's/;date.timezone =.*/date.timezone = ${TZ}/' $PHP_INI_DIR/php.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dependencies
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-plugins --no-scripts --no-dev --prefer-dist --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci

FROM dependencies AS build
COPY . .
RUN npm run build

FROM base AS final

COPY --from=build /var/www/html /var/www/html

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs \
    && mkdir -p bootstrap/cache \
    && mkdir -p /var/www/html/public/vendor \
    && mkdir -p /var/log/supervisor /var/run/supervisor \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan livewire:publish --assets

RUN apk del .build-deps

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/vendor \
    && chown -R www-data:www-data /var/log/supervisor /var/run/supervisor \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/vendor

COPY docker/stg/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/stg/supervisor/horizon.conf /etc/supervisor/conf.d/horizon.conf

USER www-data

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

EXPOSE 9000
