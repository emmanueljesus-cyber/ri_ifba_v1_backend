# Dockerfile - Laravel 12 + PHP 8.4 (Railway-safe) + toggles
FROM php:8.4-cli

WORKDIR /var/www/html

# 1) Dependências do sistema + extensões PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2) Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3) Copia composer.* primeiro para cache
COPY composer.json composer.lock ./

# 4) Instala deps sem scripts (evita artisan no build)
RUN COMPOSER_ALLOW_SUPERUSER=1 \
    composer install \
      --no-interaction \
      --prefer-dist \
      --optimize-autoloader \
      --no-dev \
      --no-scripts

# 5) Copia o restante do código
COPY . .

# 6) Regera autoload com o código presente
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# 7) Storage/cache perms
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# 8) Start script (usa ENV do Railway; não cria .env)
RUN printf '%s\n' \
'#!/bin/sh' \
'set -e' \
'' \
'cd /var/www/html' \
'' \
'echo "==> Env: APP_ENV=${APP_ENV:-undefined} | APP_DEBUG=${APP_DEBUG:-undefined} | PORT=${PORT:-8000}"' \
'echo "==> Toggles: RUN_SETUP=${RUN_SETUP:-false} | CACHE_CONFIG=${CACHE_CONFIG:-false}"' \
'' \
'# Evita ficar preso em config antiga (muito comum em deploy)' \
'echo "==> Clearing caches..."' \
'php artisan optimize:clear || true' \
'' \
'# Como o build usa --no-scripts, garantimos discover no runtime' \
'echo "==> package:discover..."' \
'php artisan package:discover --ansi || true' \
'' \
'# Opcional: cachear config em produção (só se você quiser)' \
'if [ "${CACHE_CONFIG:-false}" = "true" ]; then' \
'  echo "==> Caching config..."' \
'  php artisan config:cache || true' \
'fi' \
'' \
'# Opcional: rodar seu setup (migrations/seeds/etc) só quando você mandar' \
'if [ "${RUN_SETUP:-false}" = "true" ]; then' \
'  echo "==> Running project setup..."' \
'  php artisan project:setup || echo "Setup failed, continuing..."' \
'else' \
'  echo "==> Skipping project setup (RUN_SETUP != true)"' \
'fi' \
'' \
'echo "==> Starting Laravel server on port ${PORT:-8000}..."' \
'php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' \
> /usr/local/bin/start-app.sh \
    && chmod +x /usr/local/bin/start-app.sh

EXPOSE 8000

CMD ["sh", "/usr/local/bin/start-app.sh"]
