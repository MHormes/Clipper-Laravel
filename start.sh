#!/bin/sh
set -e

# Wait for database to be ready (Optional but professional)
echo "Waiting for database..."
until nc -z clipper_postgres 5432; do
  sleep 1
done

echo "Database is up - executing migrations..."
php artisan migrate --force

echo "Clearing cache..."
php artisan config:clear
php artisan route:clear

# Execute the main container process (Apache)
exec apache2-foreground