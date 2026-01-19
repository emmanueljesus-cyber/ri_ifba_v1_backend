# Dockerfile para Laravel 12 + PHP 8.4
FROM php:8.4-cli

WORKDIR /var/www/html

# Dependências do sistema
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

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Criar script de inicialização
RUN echo '#!/bin/sh\n\
cd /var/www/html\n\
if [ ! -d "vendor" ]; then\n\
  echo "Installing Composer dependencies..."\n\
  composer install --no-interaction --prefer-dist --optimize-autoloader\n\
fi\n\
echo "Starting Laravel development server..."\n\
php artisan serve --host=0.0.0.0 --port=8000' > /usr/local/bin/start-app.sh \
    && chmod +x /usr/local/bin/start-app.sh

EXPOSE 8000

CMD ["sh", "/usr/local/bin/start-app.sh"]
