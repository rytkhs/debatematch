#!/bin/sh
set -eu

: "${REDIS_PASSWORD:?REDIS_PASSWORD is required}"

TMP_CONF="/tmp/redis.conf"
cp /usr/local/etc/redis/redis.conf "$TMP_CONF"

if ! grep -qE '^\s*requirepass\s+' "$TMP_CONF"; then
  echo "requirepass ${REDIS_PASSWORD}" >> "$TMP_CONF"
fi

exec redis-server "$TMP_CONF"
