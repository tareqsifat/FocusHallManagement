#!/bin/sh
set -e

cd /var/www/html

# The storage volume may be empty on first run
mkdir -p storage/app/public storage/app/private storage/logs \
    storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/framework/testing bootstrap/cache

# Production passes APP_KEY from the server's .env. Local `docker compose up`
# has none, so generate one once and keep it in the storage volume, which
# keeps sessions valid across rebuilds.
if [ -z "$APP_KEY" ]; then
    key_file=storage/app/.app-key
    if [ ! -s "$key_file" ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$key_file"
        echo "Generated APP_KEY in $key_file"
    fi
    APP_KEY="$(cat "$key_file")"
    export APP_KEY
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Section 10 demo data and the demo logins, only into an empty database
if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
    php docker/seed-if-empty.php
fi

# Config, routes, events and views are fixed for this container's lifetime
if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
