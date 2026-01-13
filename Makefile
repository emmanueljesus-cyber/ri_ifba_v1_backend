# ========================================
# Makefile - RI IFBA Backend
# ========================================

# Detectar se usa $(DOCKER_COMPOSE) ou docker compose
DOCKER_COMPOSE := $(shell command -v $(DOCKER_COMPOSE) 2> /dev/null)
ifndef DOCKER_COMPOSE
	DOCKER_COMPOSE := docker compose
endif

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
	$(DOCKER_COMPOSE) build

up: ## Iniciar containers em background
	$(DOCKER_COMPOSE) up -d

down: ## Parar containers
	$(DOCKER_COMPOSE) down

restart: ## Reiniciar containers
	$(DOCKER_COMPOSE) restart

logs: ## Ver logs de todos os containers
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Ver logs da aplicação
	$(DOCKER_COMPOSE) logs -f app

logs-nginx: ## Ver logs do Nginx
	$(DOCKER_COMPOSE) logs -f nginx

logs-postgres: ## Ver logs do PostgreSQL
	$(DOCKER_COMPOSE) logs -f postgres

ps: ## Listar containers rodando
	$(DOCKER_COMPOSE) ps

shell: ## Acessar shell do container da aplicação
	$(DOCKER_COMPOSE) exec app bash

tinker: ## Abrir Laravel Tinker
	$(DOCKER_COMPOSE) exec app php artisan tinker

# ========================================
# BANCO DE DADOS
# ========================================

migrate: ## Executar migrations
	$(DOCKER_COMPOSE) exec app php artisan migrate

migrate-fresh: ## Resetar banco e executar migrations
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh

seed: ## Popular banco com dados de teste
	$(DOCKER_COMPOSE) exec app php artisan db:seed

migrate-seed: ## Migrations + Seed
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

db-shell: ## Acessar shell do PostgreSQL
	$(DOCKER_COMPOSE) exec postgres psql -U postgres -d ri_ifba

db-backup: ## Fazer backup do banco de dados
	$(DOCKER_COMPOSE) exec postgres pg_dump -U postgres ri_ifba > backup_$$(date +%Y%m%d_%H%M%S).sql

db-restore: ## Restaurar backup (usar: make db-restore FILE=backup.sql)
	$(DOCKER_COMPOSE) exec -T postgres psql -U postgres ri_ifba < $(FILE)

# ========================================
# TESTES & QUALIDADE
# ========================================

test: ## Executar testes
	$(DOCKER_COMPOSE) exec app php artisan test

test-coverage: ## Executar testes com coverage
	$(DOCKER_COMPOSE) exec app php artisan test --coverage

pint: ## Formatar código (Laravel Pint)
	$(DOCKER_COMPOSE) exec app ./vendor/bin/pint

phpstan: ## Análise estática (PHPStan)
	$(DOCKER_COMPOSE) exec app ./vendor/bin/phpstan analyse

quality: pint phpstan test ## Executar todas as verificações de qualidade

# ========================================
# CACHE & OTIMIZAÇÃO
# ========================================

cache-clear: ## Limpar todos os caches
	$(DOCKER_COMPOSE) exec app php artisan cache:clear
	$(DOCKER_COMPOSE) exec app php artisan config:clear
	$(DOCKER_COMPOSE) exec app php artisan route:clear
	$(DOCKER_COMPOSE) exec app php artisan view:clear

cache-optimize: ## Otimizar caches
	$(DOCKER_COMPOSE) exec app php artisan config:cache
	$(DOCKER_COMPOSE) exec app php artisan route:cache
	$(DOCKER_COMPOSE) exec app php artisan view:cache

# ========================================
# PRODUÇÃO
# ========================================

prod-build: ## Construir imagem de produção
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE).prod.yml build

prod-up: ## Iniciar containers de produção
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE).prod.yml up -d

prod-down: ## Parar containers de produção
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE).prod.yml down

prod-logs: ## Ver logs de produção
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE).prod.yml logs -f

prod-ps: ## Listar containers de produção
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE).prod.yml ps

prod-deploy: prod-build prod-down prod-up prod-migrate ## Deploy completo

prod-migrate: ## Executar migrations em produção
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE).prod.yml exec app php artisan migrate --force

# ========================================
# LIMPEZA
# ========================================

clean: ## Parar e remover containers, volumes e imagens
	$(DOCKER_COMPOSE) down -v --remove-orphans
	docker system prune -f

clean-all: ## Limpeza completa (cuidado!)
	$(DOCKER_COMPOSE) down -v --remove-orphans
	docker system prune -af --volumes

# ========================================
# INSTALAÇÃO & SETUP
# ========================================

setup: ## Setup inicial do projeto
	@echo "🚀 Configurando projeto..."
	@if [ ! -f .env ]; then cp .env.docker .env; fi
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	@echo "⏳ Aguardando containers iniciarem..."
	@sleep 10
	$(DOCKER_COMPOSE) exec app composer install
	$(DOCKER_COMPOSE) exec app php artisan key:generate
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed
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
	$(DOCKER_COMPOSE) exec app composer install

composer-update: ## Atualizar dependências PHP
	$(DOCKER_COMPOSE) exec app composer update

artisan: ## Executar comando artisan (usar: make artisan CMD="route:list")
	$(DOCKER_COMPOSE) exec app php artisan $(CMD)

npm-install: ## Instalar dependências Node
	$(DOCKER_COMPOSE) exec app npm install

npm-dev: ## Executar npm dev
	$(DOCKER_COMPOSE) exec app npm run dev

npm-build: ## Build de produção
	$(DOCKER_COMPOSE) exec app npm run build

queue-work: ## Iniciar queue worker
	$(DOCKER_COMPOSE) exec app php artisan queue:work

queue-restart: ## Reiniciar queue workers
	$(DOCKER_COMPOSE) exec app php artisan queue:restart

# ========================================
# ADMINER
# ========================================

adminer-up: ## Iniciar Adminer (gerenciador de BD)
	$(DOCKER_COMPOSE) --profile tools up -d adminer
	@echo "Adminer disponível em: http://localhost:8080"

adminer-down: ## Parar Adminer
	$(DOCKER_COMPOSE) --profile tools stop adminer

# ========================================
# INFORMAÇÕES
# ========================================

info: ## Mostrar informações do ambiente
	@echo "========================================="
	@echo "RI IFBA - Informações do Ambiente"
	@echo "========================================="
	@echo ""
	@echo "🐘 PHP:"
	@$(DOCKER_COMPOSE) exec app php --version | head -n 1
	@echo ""
	@echo "🎼 Composer:"
	@$(DOCKER_COMPOSE) exec app composer --version
	@echo ""
	@echo "🔴 Laravel:"
	@$(DOCKER_COMPOSE) exec app php artisan --version
	@echo ""
	@echo "🗄️ PostgreSQL:"
	@$(DOCKER_COMPOSE) exec postgres psql --version
	@echo ""
	@echo "📊 Containers:"
	@$(DOCKER_COMPOSE) ps

