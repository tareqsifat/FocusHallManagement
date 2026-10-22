# ---- base: PHP runtime + Apache, shared by every PHP stage -------------------
# 8.3.36 is pinned on purpose: it is the first 8.3 release with the PHP2Shell
# RCE fixes (CVE-2026-109618, CVE-2026-109619). Never use a floating tag here.
FROM php:8.3.36-apache AS base

# PHP extensions (the base image already ships curl, mbstring, openssl, fileinfo, ...)
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl opcache \
    && rm /usr/local/bin/install-php-extensions

# Apache: serve Laravel's public/ directory and honour its .htaccess
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && a2enmod rewrite headers

# Behind the proxy, take the client IP from X-Forwarded-For so rate limiting
# and request logs are per client
RUN printf '%s\n' \
        'RemoteIPHeader X-Forwarded-For' \
        'RemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16' \
        > /etc/apache2/conf-available/remoteip.conf \
    && a2enmod remoteip \
    && a2enconf remoteip

# Small worker pool for small servers; loaded after mods-enabled, so it wins
ENV APACHE_MAX_WORKERS=10
COPY docker/apache-mpm.conf /etc/apache2/conf-available/hall-mpm.conf
RUN a2enconf hall-mpm

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini $PHP_INI_DIR/conf.d/99-hall.ini
COPY docker/opcache.ini $PHP_INI_DIR/conf.d/98-opcache.ini

WORKDIR /var/www/html

# ---- deps: vendor/ packages only, cached until composer.lock changes ---------
FROM base AS deps
# Composer needs unzip to unpack dist archives; this stage only, not the runtime
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# ---- assets: Vite build; only public/build reaches the final image ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# Tailwind also scans app/ for class names kept in PHP (e.g. badge colours on enums)
COPY app ./app
# Tailwind scans Laravel's pagination views for class names (tailwind.config.js)
COPY --from=deps /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---- autoload: optimized classmap for this commit's code ---------------------
FROM deps AS autoload
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts

# ---- runtime: no Composer, no Node, no build tools ---------------------------
FROM base AS runtime

# Compiled Blade views stay inside this container, not in the shared storage
# volume, so old and new containers do not overwrite each other mid-rollout
ENV VIEW_COMPILED_PATH=/var/www/html/bootstrap/cache/views

# Same digest as the last release unless composer.lock changed
COPY --from=deps /var/www/html/vendor ./vendor
COPY . .
COPY --from=autoload /var/www/html/vendor/composer ./vendor/composer
COPY --from=autoload /var/www/html/vendor/autoload.php ./vendor/autoload.php
COPY --from=assets /app/public/build ./public/build

RUN php artisan package:discover --ansi \
    && sed -i 's/\r$//' docker/entrypoint.sh \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p "$VIEW_COMPILED_PATH" \
    && chown -R www-data:www-data storage bootstrap/cache

# Graceful stop: Apache finishes in-flight requests before exiting, so a
# rolling deploy does not cut off requests on the old container
STOPSIGNAL SIGWINCH

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
