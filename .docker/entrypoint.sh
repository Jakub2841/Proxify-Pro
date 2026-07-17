#!/bin/sh
set -e

cp /var/www/html/.env.docker /var/www/html/.env

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisor.d/proxify.ini
