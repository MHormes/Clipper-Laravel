#!/bin/sh
set -e

cd "$(dirname "$0")/.."

echo "Waiting for database (${DB_HOST}) before starting queue worker..."
until php -r "try { \$p = new PDO('pgsql:host=${DB_HOST};port=5432;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); exit(0); } catch (Exception \$e) { exit(1); }"; do
  sleep 1
done

echo "Starting Laravel queue worker..."
exec php artisan queue:work --tries=3 --timeout=90 --sleep=3
