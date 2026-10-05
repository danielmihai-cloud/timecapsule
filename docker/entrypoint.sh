#!/bin/sh
# Runs every time the container starts, then hands over to Apache.
set -e
cd /var/www/html

echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" >/dev/null 2>&1; do
  sleep 1
done

# Demo only: if no APP_KEY was given, generate one once and keep it in a file, so a
# container restart does not log everybody out (sessions are encrypted with this key).
# On a real server set APP_KEY in .env instead.
if [ -z "$APP_KEY" ]; then
  KEY_FILE=storage/app/.app_key
  if [ ! -s "$KEY_FILE" ]; then
    echo "base64:$(head -c 32 /dev/urandom | base64)" > "$KEY_FILE"
    echo "APP_KEY was empty, generated one in $KEY_FILE."
  fi
  export APP_KEY="$(cat "$KEY_FILE")"
fi

# Create/upgrade the tables and the Guest user (both are safe to run on every start).
php artisan migrate --force --seed

# Cache the configuration for speed. Must run on every start, because env vars
# (e.g. AUTH_ENABLED) can be different each time the container is created.
php artisan config:cache

# The web server runs as www-data and must be able to write sessions, logs and uploads
# (the uploads folder is a Docker volume, which starts out owned by root).
mkdir -p storage/app/uploads
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
