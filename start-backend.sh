#!/bin/bash

# 🍽️ RI IFBA Backend - Inicializador
echo "🍽️ Iniciando RI IFBA Backend..."

# Verifica se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker não está rodando! Inicie o Docker Desktop primeiro."
    exit 1
fi

# Mata processos que possam estar usando as portas
echo "🧹 Limpando portas em uso..."
sudo pkill -f "redis-server" 2>/dev/null || true
sudo pkill -f "postgres" 2>/dev/null || true

# Para containers existentes e remove volumes
echo "🔄 Parando containers antigos..."
docker compose down -v

# Remove containers órfãos e imagens antigas
echo "🗑️ Limpando containers órfãos..."
docker container prune -f
docker network prune -f

# Reconstrói e inicia containers
echo "🏗️ Construindo e iniciando containers..."
docker compose build --no-cache
docker compose up -d

#!/bin/bash

# 🍽️ RI IFBA Backend - Inicializador
echo "🍽️ Iniciando RI IFBA Backend..."

# Verifica se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker não está rodando! Inicie o Docker Desktop primeiro."
    exit 1
fi

# Para containers existentes e remove volumes
echo "🔄 Parando containers antigos..."
docker compose down -v

# Reconstrói e inicia containers
echo "🏗️ Construindo e iniciando containers..."
docker compose up -d --build

# Aguarda containers iniciarem
echo "⏳ Aguardando containers iniciarem..."
sleep 15

# Verifica status mais detalhado
echo "📊 Status dos containers:"
docker compose ps

# Aguarda PostgreSQL ficar pronto (com mais tempo)
echo "🗄️ Aguardando PostgreSQL ficar pronto..."
for i in {1..60}; do
    if docker compose exec -T postgres pg_isready -U postgres -d ri_ifba_v1 > /dev/null 2>&1; then
        echo "✅ PostgreSQL está pronto!"
        break
    fi
    if [ $i -eq 60 ]; then
        echo "❌ PostgreSQL não ficou pronto em 60 tentativas"
        echo "📋 Logs do PostgreSQL:"
        docker compose logs postgres --tail 20
        exit 1
    fi
    echo "  Tentativa $i/60... aguardando 2s"
    sleep 2
done

# Instala dependências se necessário
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Instalando dependências PHP..."
    docker compose exec app composer install --no-interaction --prefer-dist
fi

# Gera chave se necessário
echo "🔐 Verificando chave da aplicação..."
docker compose exec app php artisan key:generate --force

# Roda migrações
echo "🗄️ Executando migrações..."
docker compose exec app php artisan migrate --seed

# Limpa cache
echo "🧹 Limpando caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear

# Mostra informações de acesso
echo ""
echo "✅ Backend iniciado com sucesso!"
echo ""
echo "🔗 URLs de acesso:"
echo "   API: http://localhost:8000/api/v1"
echo "   Alternativa: http://localhost:8001/api/v1"
echo "   Teste: curl http://localhost:8000/api/v1/cardapio/hoje"
echo ""

# Mostra IP do WSL para configuração no frontend
WSL_IP=$(hostname -I | awk '{print $1}')
echo "🌐 Para conectar do Windows ao WSL:"
echo "   IP do WSL: ${WSL_IP}"
echo "   API URL: http://${WSL_IP}:8000/api/v1"
echo "   API URL (Alt): http://${WSL_IP}:8001/api/v1"
echo ""

# Testa conectividade
echo "🧪 Testando conectividade..."
if curl -s http://localhost:8000/api/v1/health > /dev/null 2>&1; then
    echo "✅ API respondendo em http://localhost:8000"
else
    echo "⚠️ API não está respondendo ainda, pode precisar de mais tempo"
fi

echo ""
echo "📋 Logs em tempo real: docker compose logs -f"
echo "🛑 Para parar: docker compose down"
