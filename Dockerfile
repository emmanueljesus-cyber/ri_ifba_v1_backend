# Dockerfile para Laravel 12 + PHP 8.4
FROM php:8.4-fpm

# Metadata
LABEL maintainer="RI IFBA "
LABEL description="RI-IFBA: Sistema Digital para o Refeitório Institucional do IFBA – Backend"
LABEL version="1.0.0"

# Argumentos de build
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG NODE_VERSION=20

# Definir diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    postgresql-client \
    nano \
    vim \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Configurar OPcache para produção
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Instalar Composer
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

# Instalar Node.js e NPM (para assets)
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Criar usuário não-root
RUN groupadd -g ${GROUP_ID} laravel \
    && useradd -u ${USER_ID} -g laravel -m -s /bin/bash laravel

# Copiar arquivos do projeto
COPY --chown=laravel:laravel . /var/www/html

# Configurar permissões
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Mudar para usuário não-root
USER laravel

# Instalar dependências do Composer (otimizado para produção)
RUN composer install --optimize-autoloader --no-dev --prefer-dist --no-interaction

# Configurar PHP-FPM
USER root
RUN echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 10" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 20" >> /usr/local/etc/php-fpm.d/www.conf

# Expor porta do PHP-FPM
EXPOSE 9000

# Script de inicialização
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

