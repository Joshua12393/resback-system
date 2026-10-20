#!/bin/sh
set -e

# Render routes traffic to $PORT (default 10000).
PORT="${PORT:-10000}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=SuperAdminSeeder --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
