#!/bin/bash
set -e

echo "Running entrypoint.sh script..."

# Wait for MySQL to be ready using wait-for-it script
echo "Waiting for MySQL to be ready..."
/usr/local/bin/wait-for-it.sh mysql:3306 --timeout=60 -- echo "MySQL is up!"

if [ ! -d "vendor" ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction --optimize-autoloader --no-dev
fi

echo "Running migrations..."
php artisan migrate --force
php artisan db:seed

echo "Migrations completed successfully!"

# Continue with the rest of the application startup
exec "$@"