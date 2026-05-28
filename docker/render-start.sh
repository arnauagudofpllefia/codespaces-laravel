#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

# Ensure uploaded files in storage/app/public are reachable via /storage/*
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
