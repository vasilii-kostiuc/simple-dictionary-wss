#!/bin/bash
set -e

# Ждём Redis и Mongo перед стартом
echo "Waiting for Redis and Mongo..."
until nc -z redis 6379 && nc -z mongo 27017; do
  sleep 1
done

# Create storage directories
mkdir -p storage/framework/{sessions,views,cache} storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Применяем миграции, если нужно
php artisan migrate --force || true

exec "$@"
