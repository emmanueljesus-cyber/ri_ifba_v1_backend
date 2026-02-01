# Laravel 12 + PHP 8.4 (simples com artisan serve)
FROM php:8.4-cli

WORKDIR /var/www/html

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpq-dev libzip-dev \
    libpng-dev libjpeg-dev \
  && docker-php-ext-configure gd --with-jpeg \
  && docker-php-ext-install pdo pdo_pgsql zip gd \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 1) Copia só os manifests primeiro (pra cache)
COPY composer.json composer.lock ./

# 2) Instala deps no build (mais confiável que em runtime)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 3) Agora copia o resto do código
COPY . .

# Permissões (Laravel)
RUN mkdir -p storage bootstrap/cache \
  && chmod -R 775 storage bootstrap/cache

# Railway vai rotear para essa porta interna
EXPOSE 8000

# Start: usa PORT do Railway (se existir), senão 8000
CMD ["sh", "-lc", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
