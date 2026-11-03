#!/bin/sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT must be a valid TCP port number." >&2
        exit 1
        ;;
esac
if [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
    echo "PORT must be between 1 and 65535." >&2
    exit 1
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be configured in the hosting environment." >&2
    exit 1
fi

case "${DB_CONNECTION:-}" in
    ''|sqlite)
        echo "Configure a persistent database and set DB_CONNECTION (for example, pgsql)." >&2
        exit 1
        ;;
esac

if [ "${DB_CONNECTION}" = "pgsql" ]; then
    case "${DATABASE_URL:-}" in
        postgres://*|postgresql://*)
            ;;
        *)
            echo "Set DATABASE_URL to the external PostgreSQL connection URL in the hosting environment." >&2
            exit 1
            ;;
    esac
fi

sed -i "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s#^<VirtualHost \\*:.*>#<VirtualHost *:${port}>#" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan migrate --force
php artisan storage:link --force

exec apache2-foreground
