FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libssl-dev pkg-config libicu-dev libzip-dev netcat-openbsd\
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Устанавливаем расширение MongoDB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY ./ /var/www/html

WORKDIR /var/www/html

# Устанавливаем зависимости Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chmod +x ./_docker/wss_app/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
