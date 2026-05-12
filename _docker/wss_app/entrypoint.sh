#!/bin/bash
set -e

NODE_LABEL="${WSS_NODE_ID:-$(hostname)}"
echo "[${NODE_LABEL}] Starting up..."

# Ждём Redis и Mongo перед стартом
echo "[${NODE_LABEL}] Waiting for Redis and Mongo..."
until nc -z redis 6379 && nc -z mongo 27017; do
  sleep 1
done
echo "[${NODE_LABEL}] Redis and Mongo are ready."

# Create storage directories
mkdir -p storage/framework/{sessions,views,cache} storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Применяем миграции, если нужно
php artisan migrate --force || true

exec "$@"
