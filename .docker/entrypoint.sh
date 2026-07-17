#!/bin/sh
set -e

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisor.d/proxify.ini
