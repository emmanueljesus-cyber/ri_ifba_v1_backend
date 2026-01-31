#!/bin/bash

# ============================================================
# 🚀 Script de Setup Automático - RI IFBA Backend
# ============================================================
# Uso: curl -sSL <url-do-script> | bash
# Ou:  ./setup.sh
# ============================================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funções de log
log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

# Banner
echo -e "${GREEN}"
echo "╔══════════════════════════════════════════════════════════╗"
echo "║     🍽️  RI IFBA Backend - Setup Automático              ║"
echo "║     Sistema de Gestão de Refeições                       ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# ============================================================
# 1. Verificar pré-requisitos
# ============================================================
log_info "Verificando pré-requisitos..."

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    log_error "Docker não encontrado!"
    echo ""
    echo "Instale o Docker Desktop:"
    echo "  👉 https://www.docker.com/products/docker-desktop"
    echo ""
    echo "Depois de instalar, ative a integração WSL:"
    echo "  Settings → Resources → WSL Integration → Ubuntu"
    echo ""
    exit 1
fi
log_success "Docker encontrado"

# Verificar se Docker Compose está disponível
if ! docker compose version &> /dev/null; then
    log_error "Docker Compose não encontrado!"
    exit 1
fi
log_success "Docker Compose encontrado"

# Verificar se Docker está rodando
if ! docker info &> /dev/null; then
    log_error "Docker não está rodando!"
    echo ""
    echo "Inicie o Docker Desktop e tente novamente."
    echo ""
    exit 1
fi
log_success "Docker está rodando"

# ============================================================
# 2. Verificar permissões do Docker
# ============================================================
log_info "Verificando permissões do Docker..."

if ! docker ps &> /dev/null; then
    log_warning "Você não tem permissão para usar Docker sem sudo"
    echo ""
    echo "Execute os comandos abaixo e depois rode este script novamente:"
    echo ""
    echo "  sudo usermod -aG docker \$USER"
    echo "  newgrp docker"
    echo ""
    exit 1
fi
log_success "Permissões OK"

# ============================================================
# 3. Configurar ambiente
# ============================================================
log_info "Configurando ambiente..."

# Verificar se estamos na pasta correta
if [ ! -f "composer.json" ]; then
    log_error "Execute este script na raiz do projeto!"
    echo ""
    echo "  cd ri_ifba_v1_backend"
    echo "  ./setup.sh"
    echo ""
    exit 1
fi

# Copiar .env se não existir
if [ ! -f ".env" ]; then
    if [ -f ".env.docker" ]; then
        cp .env.docker .env
        log_success "Arquivo .env criado a partir de .env.docker"
    elif [ -f ".env.example" ]; then
        cp .env.example .env
        log_success "Arquivo .env criado a partir de .env.example"
    else
        log_error "Nenhum arquivo .env de exemplo encontrado!"
        exit 1
    fi
else
    log_success "Arquivo .env já existe"
fi

# ============================================================
# 4. Parar containers antigos (se existirem)
# ============================================================
log_info "Parando containers antigos (se existirem)..."
docker compose down 2>/dev/null || true
log_success "Containers antigos parados"

# ============================================================
# 5. Build das imagens
# ============================================================
log_info "Construindo imagens Docker (pode demorar alguns minutos na primeira vez)..."
docker compose build --quiet
log_success "Imagens construídas"

# ============================================================
# 6. Subir containers
# ============================================================
log_info "Iniciando containers..."
docker compose up -d
log_success "Containers iniciados"

# ============================================================
# 7. Aguardar banco de dados ficar pronto
# ============================================================
log_info "Aguardando banco de dados ficar pronto..."
sleep 5

# Verificar se PostgreSQL está saudável
RETRIES=30
until docker compose exec -T postgres pg_isready -U postgres &> /dev/null || [ $RETRIES -eq 0 ]; do
    echo -n "."
    sleep 1
    ((RETRIES--))
done
echo ""

if [ $RETRIES -eq 0 ]; then
    log_error "Banco de dados não iniciou a tempo!"
    docker compose logs postgres
    exit 1
fi
log_success "Banco de dados pronto"

# ============================================================
# 8. Instalar dependências do Composer
# ============================================================
log_info "Instalando dependências do Composer..."
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader 2>/dev/null || {
    log_warning "Composer install falhou, tentando novamente..."
    docker compose exec -T app composer install --no-interaction
}
log_success "Dependências instaladas"

# ============================================================
# 9. Gerar APP_KEY se necessário
# ============================================================
log_info "Verificando APP_KEY..."
APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    docker compose exec -T app php artisan key:generate --force
    log_success "APP_KEY gerado"
else
    log_success "APP_KEY já existe"
fi

# ============================================================
# 10. Executar migrations
# ============================================================
log_info "Executando migrations..."
docker compose exec -T app php artisan migrate --force
log_success "Migrations executadas"

# ============================================================
# 11. Popular banco com dados de teste
# ============================================================
log_info "Populando banco com dados de teste..."
docker compose exec -T app php artisan db:seed --force 2>/dev/null || {
    log_warning "Seed já foi executado anteriormente (dados já existem)"
}
log_success "Banco populado"

# ============================================================
# 12. Limpar e otimizar cache
# ============================================================
log_info "Otimizando caches..."
docker compose exec -T app php artisan config:cache 2>/dev/null || true
docker compose exec -T app php artisan route:cache 2>/dev/null || true
log_success "Caches otimizados"

# ============================================================
# 13. Verificar status final
# ============================================================
echo ""
log_info "Verificando status dos containers..."
echo ""
docker compose ps
echo ""

# ============================================================
# 14. Testar API
# ============================================================
log_info "Testando API..."
sleep 2
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/v1/cardapio/hoje 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    log_success "API respondendo corretamente!"
elif [ "$HTTP_CODE" = "404" ]; then
    log_warning "API rodando, mas rota retornou 404 (pode não haver cardápio para hoje)"
else
    log_warning "API pode ainda estar inicializando (código: $HTTP_CODE)"
fi

# ============================================================
# Finalização
# ============================================================
echo ""
echo -e "${GREEN}"
echo "╔══════════════════════════════════════════════════════════╗"
echo "║     🎉 Setup Concluído com Sucesso!                      ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"
echo ""
echo -e "${BLUE}📍 Acessos:${NC}"
echo "   • API:     http://localhost:8000/api/v1/cardapio/hoje"
echo "   • Adminer: http://localhost:8080"
echo ""
echo -e "${BLUE}🔐 Login Admin:${NC}"
echo "   • Matrícula: 10000000001"
echo "   • Senha:     password"
echo ""
echo -e "${BLUE}📝 Comandos úteis:${NC}"
echo "   • Ver logs:      docker compose logs -f"
echo "   • Parar tudo:    docker compose down"
echo "   • Reiniciar:     docker compose restart"
echo "   • Shell do app:  docker compose exec app bash"
echo ""
