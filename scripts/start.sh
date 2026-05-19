#!/bin/sh
set -e

# Move to the root of the project
cd "$(dirname "$0")/.."

echo "Waiting for database (${DB_HOST})..."
until php -r "try { \$p = new PDO('pgsql:host=${DB_HOST};port=5432;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); exit(0); } catch (Exception \$e) { exit(1); }"; do
  sleep 1
done

if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Vendor folder missing or incomplete. Installing dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

echo "Database is up - executing migrations..."
php artisan migrate --force

# SMART SEEDING LOGIC
# We check if there are any users. If count is 0, we run the seeder.
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\Clipper::count();")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "🌱 First run detected. Seeding data from CSVs..."
    php artisan db:seed --class=CsvDataSeeder --force
else
    echo "✅ Data already exists. Skipping seeder."
fi

# STORAGE SEEDING LOGIC
# Wait for AIStor/S3 to be reachable, then seed if bucket is empty.
echo "Waiting for storage (${AWS_ENDPOINT})..."
until php -r "
    \$opts = ['http' => ['timeout' => 2, 'ignore_errors' => true]];
    \$ctx = stream_context_create(\$opts);
    \$r = @file_get_contents('${AWS_ENDPOINT}/minio/health/live', false, \$ctx);
    exit(\$r !== false ? 0 : 1);
"; do
  sleep 2
done

echo "Seeding storage..."
php artisan storage:seed

echo "Starting SSR Node server..."
node /var/www/html/bootstrap/ssr/ssr.mjs &

echo "Clearing cache and starting Apache..."
php artisan config:clear
exec apache2-foreground