#!/bin/sh
set -e

sleep 15

[ -f database/database.sqlite ] || touch database/database.sqlite
[ -f database/lookup.sqlite ] || touch database/lookup.sqlite

php artisan config:clear
php artisan route:clear
php artisan key:generate --ansi

php artisan vendor:publish \
    --provider="ScalableDB\ScalableDBServiceProvider" \
    --tag="scalable-db-migrations"

php artisan migrate \
  --database=sqlite \
  --path=database/migrations/default \
  --force

php artisan migrate \
  --database=lookup \
  --path=database/migrations/lookup \
  --force

php artisan cache:clear

php artisan shard:migrate --path=database/migrations --force

php artisan shard:seed

exec php artisan serve --host=0.0.0.0 --port=8000