FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
        git zip unzip libzip-dev libonig-dev libsqlite3-dev\
 && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN composer create-project --prefer-dist laravel/laravel:^11.0 .

COPY . scalable-db

COPY demo/overrides/config/database.php /app/config/database.php
COPY demo/overrides/routes/web.php      /app/routes/web.php

RUN composer config repositories.scalabledb path ./scalable-db \
 && composer require morozmkhl/scalable-db:dev-main \
        --no-interaction --prefer-dist

RUN php artisan telescope:install \
 && php artisan key:generate --ansi

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]



