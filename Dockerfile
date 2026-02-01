## Dockerfile para Laravel 12 + PHP 8.4
#FROM php:8.4-cli
#
#WORKDIR /var/www/html
#
## Dependências do sistema
#RUN apt-get update && apt-get install -y \
#    git \
#    curl \
#    zip \
#    unzip \
#    libpq-dev \
#    libzip-dev \
#    libpng-dev \
#    libjpeg-dev \
#    && docker-php-ext-configure gd --with-jpeg \
#    && docker-php-ext-install pdo pdo_pgsql zip gd \
#    && apt-get clean \
#    && rm -rf /var/lib/apt/lists/*
#
## Instalar Composer
#COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
#
## 1) Copiar composer.json e composer.lock primeiro (para cache de camadas)
#COPY composer.json composer.lock ./
#
## 2) Copiar TODO o código da aplicação (necessário para package:discover)
#COPY . .
#
## 3) Criar .env temporário para evitar erros durante o build
#RUN cp .env.example .env || echo "APP_KEY=" > .env
#
## 4) Instalar dependências SEM executar scripts Artisan durante o build
#RUN COMPOSER_ALLOW_SUPERUSER=1 \
#    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts
#
## 5) Gerar autoload agora que todo o código está presente
#RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize
#
## 6) Criar diretórios necessários com permissões corretas
#RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
#    && mkdir -p storage/logs \
#    && chmod -R 775 storage bootstrap/cache
#
## 7) Criar script de inicialização que roda setup e inicia o servidor
#RUN echo '#!/bin/sh\n\
#set -e\n\
#cd /var/www/html\n\
#\n\
## Verificar se .env existe, senão criar\n\
#if [ ! -f ".env" ]; then\n\
#  echo "Creating .env from .env.example..."\n\
#  cp .env.example .env || echo "APP_KEY=" > .env\n\
#fi\n\
#\n\
## Executar setup (migrations, seeds, etc)\n\
#echo "Running project setup..."\n\
#php artisan project:setup || echo "Setup failed, continuing..."\n\
#\n\
## Iniciar servidor\n\
#echo "Starting Laravel server on port ${PORT:-8000}..."\n\
#php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' > /usr/local/bin/start-app.sh \
#    && chmod +x /usr/local/bin/start-app.sh
#
#EXPOSE 8000
#
#CMD ["sh", "/usr/local/bin/start-app.sh"]
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

# 1) Copiar composer.json e composer.lock primeiro (para cache de camadas)
COPY composer.json composer.lock ./

# 2) Copiar TODO o código da aplicação (necessário para package:discover)
COPY . .

# 3) Criar .env temporário para evitar erros durante o build
RUN cp .env.example .env || echo "APP_KEY=" > .env

# 4) Instalar dependências SEM executar scripts Artisan durante o build
RUN COMPOSER_ALLOW_SUPERUSER=1 \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

# 5) Gerar autoload agora que todo o código está presente
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# 6) Criar diretórios necessários com permissões corretas
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p storage/logs \
    && chmod -R 775 storage bootstrap/cache

# 7) Criar script de inicialização que roda setup e inicia o servidor
RUN echo '#!/bin/sh\n\
set -e\n\
cd /var/www/html\n\
\n\
if [ ! -f ".env" ]; then\n\
  echo "Creating .env from .env.example..."\n\
  cp .env.example .env || echo "APP_KEY=" > .env\n\
fi\n\
\n\
echo "Running project setup..."\n\
php artisan project:setup || echo "Setup failed, continuing..."\n\
\n\
echo "Starting Laravel server on port ${PORT:-8000}..."\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' > /usr/local/bin/start-app.sh \
    && chmod +x /usr/local/bin/start-app.sh

EXPOSE 8000

CMD ["sh", "/usr/local/bin/start-app.sh"]
