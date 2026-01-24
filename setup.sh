#!/bin/bash
set -euo pipefail

# ============================================================
# 🚀 Script de Setup Automático - RI IFBA Backend (Docker/WSL)
# ============================================================
# Uso:
#   ./setup.sh                # setup padrão (não apaga banco)
#   ./setup.sh --fresh        # APAGA TABELAS e roda migrate:fresh --seed
#   ./setup.sh --rebuild      # força build
#   ./setup.sh --fresh --rebuild
# ============================================================

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info()    { echo -e "${BLUE}info  $1${NC}"; }
log_success() { echo -e "${GREEN}ok $1${NC}"; }
log_warning() { echo -e "${YELLOW}aviso  $1${NC}"; }
log_error()   { echo -e "${RED}erro   $1${NC}"; }

# Flags
FRESH=0
REBUILD=0

for arg in "$@"; do
  case "$arg" in
    --fresh)   FRESH=1 ;;
    --rebuild) REBUILD=1 ;;
    -h|--help)
      echo "Uso:"
      echo "  ./setup.sh [--fresh] [--rebuild]"
      exit 0
      ;;
    *)
      log_error "Argumento desconhecido: $arg"
      exit 1
      ;;
  esac
done

echo -e "${GREEN}"
echo "╔══════════════════════════════════════════════════════════╗"
echo "║      RI IFBA Backend - Setup Automatico                 ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# ============================================================
# 1) Pré-requisitos
# ============================================================
log_info "Verificando pré-requisitos..."
command -v docker >/dev/null 2>&1 || { log_error "Docker não encontrado!"; exit 1; }
docker compose version >/dev/null 2>&1 || { log_error "Docker Compose não encontrado!"; exit 1; }
docker info >/dev/null 2>&1 || { log_error "Docker não está rodando (abra o Docker Desktop)."; exit 1; }
docker ps >/dev/null 2>&1 || { log_warning "Sem permissão Docker sem sudo."; exit 1; }
log_success "Docker e Compose OK"

# ============================================================
# 2) Pasta correta
# ============================================================
log_info "Verificando diretório do projeto..."
if [ ! -f "composer.json" ] || [ ! -f "artisan" ] || [ ! -f "docker-compose.yml" ]; then
  log_error "Execute este script na raiz do backend (onde existem docker-compose.yml, composer.json e artisan)."
  exit 1
fi
log_success "Diretório OK"

# ============================================================
# 3) .env
# ============================================================
log_info "Configurando .env..."
if [ ! -f ".env" ]; then
  if [ -f ".env.docker" ]; then
    cp .env.docker .env
    log_success ".env criado a partir de .env.docker"
  elif [ -f ".env.example" ]; then
    cp .env.example .env
    log_success ".env criado a partir de .env.example"
  else
    log_error "Nenhum .env de exemplo encontrado!"
    exit 1
  fi
else
  log_success ".env já existe"
fi

# ============================================================
# 4) Parar containers antigos (SEM -v = seguro)
# ============================================================
log_info "Parando containers antigos..."
docker compose down >/dev/null 2>&1 || true
log_success "Containers antigos parados"

# ============================================================
# 5) Build (opcional)
# ============================================================
if [ "$REBUILD" -eq 1 ]; then
  log_info "Buildando imagens (forçado)..."
  docker compose build
  log_success "Build concluído"
fi

# ============================================================
# 6) Subir SÓ o Postgres primeiro
# ============================================================
log_info "Subindo PostgreSQL..."
docker compose up -d postgres
log_success "PostgreSQL iniciado"

# ============================================================
# 7) Esperar Postgres ficar saudável
# ============================================================
log_info "Aguardando PostgreSQL ficar pronto..."
RETRIES=45
until docker compose exec -T postgres pg_isready -U postgres >/dev/null 2>&1 || [ $RETRIES -eq 0 ]; do
  echo -n "."
  sleep 1
  ((RETRIES--))
done
echo ""

if [ $RETRIES -eq 0 ]; then
  log_error "PostgreSQL não ficou pronto a tempo."
  docker compose logs --tail=120 postgres || true
  exit 1
fi
log_success "PostgreSQL OK"

# ============================================================
# 8) Composer + autoload (USANDO run --rm para não depender do app up)
# ============================================================
log_info "Garantindo dependências (Composer)..."
docker compose run --rm app sh -lc \
  "git config --global --add safe.directory /var/www/html || true; \
   COMPOSER_ALLOW_SUPERUSER=1 \
   COMPOSER_PROCESS_TIMEOUT=1200 \
   COMPOSER_CACHE_DIR=/tmp/composer-cache \
   composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress"

log_info "Gerando autoload otimizado..."
docker compose run --rm app sh -lc "git config --global --add safe.directory /var/www/html || true; composer dump-autoload -o"

docker compose run --rm app sh -lc "test -f vendor/autoload.php"
log_success "Composer OK (vendor/autoload.php existe)"

# ============================================================
# 9) APP_KEY + Migrations + Seed (também via run --rm)
# ============================================================
log_info "Verificando APP_KEY..."
if ! grep -q "^APP_KEY=base64:" .env; then
  docker compose run --rm app sh -lc "php artisan key:generate --force"
  log_success "APP_KEY gerado"
else
  log_success "APP_KEY OK"
fi

if [ "$FRESH" -eq 1 ]; then
  log_warning "Modo --fresh: APAGANDO tabelas e recriando tudo com seed!"
  docker compose run --rm app sh -lc "php artisan migrate:fresh --seed --force"
  log_success "migrate:fresh --seed concluído"
else
  log_info "Executando migrations..."
  docker compose run --rm app sh -lc "php artisan migrate --force"
  log_success "Migrations concluídas"

  log_info "Executando seed..."
  docker compose run --rm app sh -lc "php artisan db:seed --force"
  log_success "Seed concluído"
fi

# ============================================================
# 10) Cache (run --rm também)
# ============================================================
log_info "Limpando e recriando caches..."
docker compose run --rm app sh -lc "php artisan optimize:clear || true"
docker compose run --rm app sh -lc "php artisan config:cache || true"
docker compose run --rm app sh -lc "php artisan route:cache  || true"
docker compose run --rm app sh -lc "php artisan view:cache   || true"
docker compose run --rm app sh -lc "php artisan event:cache  || true"
log_success "Caches OK"

# ============================================================
# 11) Agora sim subir o APP (no fim)
# ============================================================
log_info "Subindo aplicação..."
docker compose up -d app
log_success "Aplicação iniciada"

# ============================================================
# 12) Status
# ============================================================
echo ""
log_info "Status dos containers:"
docker compose ps || true
echo ""

# ============================================================
# 13) Teste rápido da API
# ============================================================
log_info "Testando API..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/v1/cardapio/hoje 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
  log_success "API respondendo (200)!"
elif [ "$HTTP_CODE" = "404" ]; then
  log_warning "API respondeu (404). Rota existe mas não há recurso/dado."
else
  log_warning "API pode estar iniciando (código: $HTTP_CODE). Veja logs: docker compose logs -f app"
fi

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║      Setup concluido!                                    ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}📍 API:${NC} http://localhost:8000/api/v1/cardapio/hoje"
echo -e "${BLUE}📝 Úteis:${NC}"
echo "   • Logs app:   docker compose logs -f app"
echo "   • Shell app:  docker compose exec app sh"
echo "   • Reset BD:   ./reset-db.sh"
echo ""
