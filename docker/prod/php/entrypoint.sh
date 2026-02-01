#!/bin/sh
set -eu

cd /var/www/html

# storage/bootstrap は volume で上書きされるので、起動時に必ず作る
mkdir -p \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# public volume が空の場合に、イメージ内の退避データから同期
if [ -d /opt/public-src ]; then
  if [ ! -f /var/www/html/public/index.php ]; then
    rm -rf /var/www/html/public/* || true
    cp -a /opt/public-src/. /var/www/html/public/
  fi

  if [ -d /opt/public-src/build ]; then
    rm -rf /var/www/html/public/build || true
    mkdir -p /var/www/html/public/build
    cp -a /opt/public-src/build/. /var/www/html/public/build/
  fi
fi

# public/storage を storage/app/public へリンク
rm -rf public/storage || true
ln -s ../storage/app/public public/storage || true

# volume の所有権を整える（root で起動してから www-data へ落とす）
if [ "$(id -u)" = "0" ]; then
  chown -R www-data:www-data storage bootstrap/cache public
  chmod -R ug+rwX storage bootstrap/cache
  chmod -R a+rX public
  exec /usr/local/bin/docker-php-entrypoint "$@"
else
  exec /usr/local/bin/docker-php-entrypoint "$@"
fi
