# Dockerfile - Laravel 12 + PHP 8.4 (Railway-safe)
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

# 3) Copia só composer.* primeiro (melhor cache de camadas)
COPY composer.json composer.lock ./

# 4) Instala dependências (sem scripts)
#    - sem scripts evita artisan rodando no build
RUN COMPOSER_ALLOW_SUPERUSER=1 \
    composer install \
      --no-interaction \

