# ========================================
# Makefile - RI IFBA Backend
# ========================================

.PHONY: help

help: ## Mostra esta mensagem de ajuda
	@echo "========================================="
	@echo "RI IFBA - Sistema de Gestão de Refeições"
	@echo "========================================="
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# ========================================
# DESENVOLVIMENTO
# ========================================

build: ## Construir containers de desenvolvimento
	docker-compose build

up: ## Iniciar containers em background
	docker-compose up -d

down: ## Parar containers
	docker-compose down

restart: ## Reiniciar containers
	docker-compose restart

logs: ## Ver logs de todos os containers
	docker-compose logs -f

logs-app: ## Ver logs da aplicação
	docker-compose logs -f app

logs-nginx: ## Ver logs do Nginx
	docker-compose logs -f nginx

logs-postgres: ## Ver logs do PostgreSQL
	docker-compose logs -f postgres

ps: ## Listar containers rodando
	docker-compose ps

shell: ## Acessar shell do container da aplicação
	docker-compose exec app bash

tinker: ## Abrir Laravel Tinker
	docker-compose exec app php artisan tinker

# ========================================
# BANCO DE DADOS
# ========================================

migrate: ## Executar migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Resetar banco e executar migrations
	docker-compose exec app php artisan migrate:fresh

seed: ## Popular banco com dados de teste
	docker-compose exec app php artisan db:seed

migrate-seed: ## Migrations + Seed
	docker-compose exec app php artisan migrate:fresh --seed

db-shell: ## Acessar shell do PostgreSQL
	docker-compose exec postgres psql -U postgres -d ri_ifba

db-backup: ## Fazer backup do banco de dados
	docker-compose exec postgres pg_dump -U postgres ri_ifba > backup_$$(date +%Y%m%d_%H%M%S).sql

db-restore: ## Restaurar backup (usar: make db-restore FILE=backup.sql)
	docker-compose exec -T postgres psql -U postgres ri_ifba < $(FILE)

# ========================================
# TESTES & QUALIDADE
# ========================================

test: ## Executar testes
	docker-compose exec app php artisan test

test-coverage: ## Executar testes com coverage
	docker-compose exec app php artisan test --coverage

pint: ## Formatar código (Laravel Pint)
	docker-compose exec app ./vendor/bin/pint

phpstan: ## Análise estática (PHPStan)
	docker-compose exec app ./vendor/bin/phpstan analyse

quality: pint phpstan test ## Executar todas as verificações de qualidade

# ========================================
# CACHE & OTIMIZAÇÃO
# ========================================

cache-clear: ## Limpar todos os caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

cache-optimize: ## Otimizar caches
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

# ========================================
# PRODUÇÃO
# ========================================

prod-build: ## Construir imagem de produção
	docker-compose -f docker-compose.prod.yml build

prod-up: ## Iniciar containers de produção
	docker-compose -f docker-compose.prod.yml up -d

prod-down: ## Parar containers de produção
	docker-compose -f docker-compose.prod.yml down

prod-logs: ## Ver logs de produção
	docker-compose -f docker-compose.prod.yml logs -f

prod-ps: ## Listar containers de produção
	docker-compose -f docker-compose.prod.yml ps

prod-deploy: prod-build prod-down prod-up prod-migrate ## Deploy completo

prod-migrate: ## Executar migrations em produção
	docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# ========================================
# LIMPEZA
# ========================================

clean: ## Parar e remover containers, volumes e imagens
	docker-compose down -v --remove-orphans
	docker system prune -f

clean-all: ## Limpeza completa (cuidado!)
	docker-compose down -v --remove-orphans
	docker system prune -af --volumes

# ========================================
# INSTALAÇÃO & SETUP
# ========================================

setup: ## Setup inicial do projeto
	@echo "🚀 Configurando projeto..."
	@if [ ! -f .env ]; then cp .env.docker .env; fi
	docker-compose build
	docker-compose up -d
	@echo "⏳ Aguardando containers iniciarem..."
	@sleep 10
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate:fresh --seed
	@echo "✅ Setup concluído!"
	@echo ""
	@echo "📋 Acesse:"
	@echo "   - Backend: http://localhost:8000"
	@echo "   - Adminer: http://localhost:8080"
	@echo ""
	@echo "🔑 Credenciais:"
	@echo "   - Admin: 10000000001 / password"
	@echo "   - Bolsista: 20231160001 / password"

fresh: ## Instalação limpa completa
	@make clean
	@make setup

# ========================================
# UTILITÁRIOS
# ========================================

composer-install: ## Instalar dependências PHP
	docker-compose exec app composer install

composer-update: ## Atualizar dependências PHP
	docker-compose exec app composer update

artisan: ## Executar comando artisan (usar: make artisan CMD="route:list")
	docker-compose exec app php artisan $(CMD)

npm-install: ## Instalar dependências Node
	docker-compose exec app npm install

npm-dev: ## Executar npm dev
	docker-compose exec app npm run dev

npm-build: ## Build de produção
	docker-compose exec app npm run build

queue-work: ## Iniciar queue worker
	docker-compose exec app php artisan queue:work

queue-restart: ## Reiniciar queue workers
	docker-compose exec app php artisan queue:restart

# ========================================
# ADMINER
# ========================================

adminer-up: ## Iniciar Adminer (gerenciador de BD)
	docker-compose --profile tools up -d adminer
	@echo "Adminer disponível em: http://localhost:8080"

adminer-down: ## Parar Adminer
	docker-compose --profile tools stop adminer

# ========================================
# INFORMAÇÕES
# ========================================

info: ## Mostrar informações do ambiente
	@echo "========================================="
	@echo "RI IFBA - Informações do Ambiente"
	@echo "========================================="
	@echo ""
	@echo "🐘 PHP:"
	@docker-compose exec app php --version | head -n 1
	@echo ""
	@echo "🎼 Composer:"
	@docker-compose exec app composer --version
	@echo ""
	@echo "🔴 Laravel:"
	@docker-compose exec app php artisan --version
	@echo ""
	@echo "🗄️ PostgreSQL:"
	@docker-compose exec postgres psql --version
	@echo ""
	@echo "📊 Containers:"
	@docker-compose ps

