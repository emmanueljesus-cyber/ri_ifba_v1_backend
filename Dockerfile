# Dockerfile - Laravel 12 + PHP 8.4 (Railway-safe)
FROM php:8.4-cli

WORKDIR /var/www/html

# 1) Dependências do sistema + extensões PHP necessárias
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

# 3) Copia composer.* primeiro para aproveitar cache das camadas
COPY composer.json composer.lock ./

# 4) Instala dependências sem scripts (não roda artisan no build)
RUN COMPOSER_ALLOW_SUPERUSER=1 \
    composer install \
      --no-interaction \
      --prefer-dist \
      --optimize-autoloader \
      --no-dev \
      --no-scripts

# 5) Agora copia o restante do código da aplicação
COPY . .

# 6) Regera autoload com o código presente (opcional, mas ok)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# 7) Cria diretórios e ajusta permissões
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# 8) Script de inicialização (NÃO cria .env; usa ENV do Railway)
RUN printf '%s\n' \
'#!/bin/sh' \
'set -e' \
'' \
'cd /var/www/html' \
'' \
'echo "==> Checking environment..."' \
'echo "APP_ENV=${APP_ENV:-undefined} | APP_DEBUG=${APP_DEBUG:-undefined} | PORT=${PORT:-8000}"' \
'' \
'echo "==> Clearing caches (avoid stale config)..."' \
'php artisan optimize:clear || true' \
'' \
'echo "==> Discovering packages (since build used --no-scripts)..."' \
'php artisan package:discover --ansi || true' \
'' \
'echo "==> Running project setup (migrations/seeds/etc)..."' \
'php artisan project:setup || echo "Setup failed, continuing..."' \
'' \
'echo "==> Starting Laravel server on port ${PORT:-8000}..."' \
'php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' \
> /usr/local/bin/start-app.sh \
    && chmod +x /usr/local/bin/start-app.sh

EXPOSE 8000

CMD ["sh", "/usr/local/bin/start-app.sh"]
