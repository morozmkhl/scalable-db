#!/bin/sh
set -e

sleep 15

[ -f database/lookup.sqlite ] || touch database/lookup.sqlite

php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan key:generate --ansi

php artisan vendor:publish \
    --provider="ScalableDB\ScalableDBServiceProvider" \
    --tag="scalable-db-migrations"

echo "🔧 Default-миграции (sqlite + Telescope)"
php artisan migrate \
  --database=sqlite \
  --path=database/migrations/default \
  --force

#echo "🔧 Migrating lookup (sqlite)…"
php artisan migrate \
  --database=lookup \
  --path=database/migrations/lookup \
  --force || true

echo "🔧 Migrating shards (users)…"
php artisan shard:migrate --path=database/migrations --force || true



echo "🔧 Seeding shards…"
php artisan shard:seed || true

if ! php artisan migrate:status | grep -q 'create_telescope_entries_table'; then
  echo "🔧 Installing Telescope…"
  php artisan telescope:install --ansi
fi

exec php artisan serve --host=0.0.0.0 --port=8000