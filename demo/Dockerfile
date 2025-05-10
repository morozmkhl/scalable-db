FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
        git zip unzip libzip-dev libonig-dev libsqlite3-dev\
 && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN composer create-project --prefer-dist laravel/laravel:^11.0 .
COPY . scalable-db

COPY demo/.env /app/.env
COPY demo/overrides/config/database.php /app/config/database.php
COPY demo/overrides/routes/web.php      /app/routes/web.php
COPY demo/overrides/bootstrap/app.php /app/bootstrap/app.php
RUN composer dump-autoload
RUN composer config repositories.scalabledb path ./scalable-db \
 && composer require morozmkhl/scalable-db \
        --no-interaction --prefer-dist

COPY demo/start.sh /app/start.sh
RUN chmod +x /app/start.sh

EXPOSE 8000



