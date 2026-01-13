#!/bin/bash
set -e

echo "🚀 Iniciando aplicação Laravel..."

# Aguardar o banco de dados estar pronto
echo "⏳ Aguardando PostgreSQL..."
until pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME
do
  echo "Aguardando PostgreSQL ficar pronto..."
  sleep 2
done
echo "✅ PostgreSQL está pronto!"

# Verificar se .env existe
if [ ! -f .env ]; then
    echo "⚠️  Arquivo .env não encontrado. Copiando de .env.example..."
    cp .env.example .env
fi

# Gerar APP_KEY se não existir ou estiver vazio
APP_KEY_VALUE=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
if [ -z "$APP_KEY_VALUE" ] || [ "$APP_KEY_VALUE" = "" ]; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate --force --ansi
    echo "✅ APP_KEY gerado com sucesso!"
elif ! echo "$APP_KEY_VALUE" | grep -q "base64:"; then
    echo "🔑 APP_KEY inválido, gerando novo..."
    php artisan key:generate --force --ansi
    echo "✅ APP_KEY gerado com sucesso!"
else
    echo "✅ APP_KEY já existe e é válido"
fi

# Limpar cache
echo "🧹 Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção (se APP_ENV=production)
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Otimizando para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Executar migrations
echo "📊 Executando migrations..."
php artisan migrate --force

# Criar link simbólico storage
if [ ! -L public/storage ]; then
    echo "🔗 Criando link simbólico do storage..."
    php artisan storage:link
fi

# Configurar permissões
echo "🔒 Configurando permissões..."
chmod -R 775 storage bootstrap/cache
chown -R laravel:laravel storage bootstrap/cache

echo "✅ Aplicação Laravel iniciada com sucesso!"

# Executar comando passado (php-fpm, queue:work, etc)
exec "$@"

