#!/bin/bash

set -e

echo "Waiting for MySQL..."
/usr/local/bin/wait-for-it.sh mysql:3306 --timeout=60 --strict

echo "Setting up development environment..."

# Ensure we're in the right directory
cd /var/www/html

# Clear PHP OPcache only (no database dependency)
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPCache reset.'; }"

echo "Running database migrations..."
php artisan migrate:fresh --force --no-interaction

# Create Xdebug log file with proper permissions
touch /var/log/xdebug.log
chown www-data:www-data /var/log/xdebug.log
chmod 666 /var/log/xdebug.log



echo "Development setup complete!"

exec "$@"