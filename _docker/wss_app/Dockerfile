FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libssl-dev pkg-config libicu-dev libzip-dev netcat-openbsd\
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd intl zip sockets

# Устанавливаем расширение MongoDB
RUN pecl install mongodb-2.1.0 \
    && docker-php-ext-enable mongodb

# Устанавливаем драйвер покрытия тестов
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY ./ /var/www/html

WORKDIR /var/www/html

RUN chmod +x ./_docker/wss_app/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
