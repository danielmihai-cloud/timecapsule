# TimeCapsule (Laravel) — Docker image.
# Stage 1 installs the PHP dependencies with Composer, stage 2 is the real web server.

# ---- Stage 1: vendor/ ---------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: artisan is not copied yet; package discovery runs in stage 2.
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    --no-scripts --no-autoloader --ignore-platform-reqs  # PHP + extensions are checked in stage 2
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---- Stage 2: Apache + PHP 8.3 ------------------------------------------------
FROM php:8.3-apache

# pdo_pgsql = PostgreSQL driver for PHP. postgresql-client gives pg_isready (used by the entrypoint).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev postgresql-client \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Upload limits: a bit above MAX_UPLOAD_MB (5), so the app can show its own error message.
RUN { \
      echo 'upload_max_filesize = 6M'; \
      echo 'post_max_size = 8M'; \
      echo 'expose_php = Off'; \
    } > /usr/local/etc/php/conf.d/timecapsule.ini

# Apache: serve only the public/ folder, let Laravel's public/.htaccess route every request.
RUN a2enmod rewrite \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
RUN php artisan package:discover --ansi \
    && mkdir -p storage/app/uploads storage/framework/sessions storage/framework/views storage/framework/cache storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
