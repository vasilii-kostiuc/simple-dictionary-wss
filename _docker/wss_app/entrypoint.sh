#!/bin/bash
set -e

# Ждём Redis и Mongo перед стартом
echo "Waiting for Redis and Mongo..."
until nc -z redis 6379 && nc -z mongo 27017; do
  sleep 1
done

# Применяем миграции, если нужно
php artisan migrate --force || true

exec "$@"
