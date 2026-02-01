FROM php:8.3-fpm-alpine AS runtime

ARG TZ=Asia/Tokyo
ENV TZ=${TZ}
ENV PHP_INI_DIR=/usr/local/etc/php

RUN apk add --no-cache \
    tzdata \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    mysql-client \
    su-exec \
  && cp /usr/share/zoneinfo/${TZ} /etc/localtime \
  && echo "${TZ}" > /etc/timezone

# Pin tag to reduce rebuild breakage
COPY --from=ghcr.io/mlocati/php-extension-installer:2.9.29 /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache --virtual .php-ext-build \
    $PHPIZE_DEPS \
    build-base \
    autoconf \
    icu-dev \
    libzip-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
  && install-php-extensions \
    exif gd intl mbstring pdo_mysql sockets zip redis pcntl opcache \
  && apk del .php-ext-build

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
  && sed -i 's/memory_limit = .*/memory_limit = 256M/' $PHP_INI_DIR/php.ini \
  && sed -i 's/post_max_size = .*/post_max_size = 50M/' $PHP_INI_DIR/php.ini \
  && sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' $PHP_INI_DIR/php.ini \
  && sed -i 's/;opcache.enable=.*/opcache.enable=1/' $PHP_INI_DIR/php.ini \
  && sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=128/' $PHP_INI_DIR/php.ini \
  && sed -i 's/;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=10000/' $PHP_INI_DIR/php.ini \
  && sed -i 's/;opcache.revalidate_freq=.*/opcache.revalidate_freq=0/' $PHP_INI_DIR/php.ini \
  && sed -i 's/;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' $PHP_INI_DIR/php.ini \
  && sed -i "s~^;*date.timezone =.*~date.timezone = ${TZ}~" $PHP_INI_DIR/php.ini

COPY docker/prod/php/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# ---- build stage (composer + node) ----
FROM runtime AS build

RUN apk add --no-cache \
    git \
    openssh-client \
    nodejs \
    npm \
    unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
# ビルド時 artisan を避けるため scripts を止める（起動後に package:discover を実行）
RUN composer install \
    --no-interaction \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build \
  && rm -rf node_modules

# ---- final: app (php-fpm) ----
FROM runtime AS app

COPY docker/prod/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html
COPY --from=build /var/www/html /var/www/html

RUN mkdir -p /opt/public-src \
  && cp -a /var/www/html/public/. /opt/public-src/

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
EXPOSE 9000

# ---- final: worker (supervisor + horizon) ----
FROM app AS worker

RUN apk add --no-cache supervisor \
 && mkdir -p /var/run/supervisor /var/log/supervisor \
 && chown -R www-data:www-data /var/run/supervisor /var/log/supervisor

COPY docker/prod/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/prod/supervisor/horizon.conf /etc/supervisor/conf.d/horizon.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
