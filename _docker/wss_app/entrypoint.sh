#!/bin/bash
set -e

# Ждём Redis и Mongo перед стартом
echo "Waiting for Redis and Mongo..."
until nc -z redis 6379 && nc -z mongo 27017; do
  sleep 1
done

# Применяем миграции, если нужно
php artisan migrate --force || true

# Запускаем Reverb
echo "Starting Laravel Reverb..."
php artisan reverb:start --host=0.0.0.0 --port=8080
