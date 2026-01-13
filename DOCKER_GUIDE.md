# 🐳 Guia Docker - RI IFBA Backend

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Requisitos](#requisitos)
- [Arquitetura](#arquitetura)
- [Instalação Rápida](#instalação-rápida)
- [Comandos Principais](#comandos-principais)
- [Desenvolvimento](#desenvolvimento)
- [Produção](#produção)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

Este projeto utiliza Docker para criar um ambiente completo e isolado com:

- ✅ **PHP 8.4 + Laravel 12.44.0**
- ✅ **PostgreSQL 16.11**
- ✅ **Redis 7** (Cache & Queue)
- ✅ **Nginx 1.25** (Servidor Web)
- ✅ **Queue Worker** (Processamento assíncrono)
- ✅ **Scheduler** (Cron jobs)
- ✅ **Adminer** (Gerenciador de BD - opcional)

---

## 📦 Requisitos

### Windows
```bash
# Instalar WSL2
wsl --install

# Instalar Docker Desktop
# Download: https://www.docker.com/products/docker-desktop

# Verificar instalação
docker --version
docker-compose --version
```

### Linux/macOS
```bash
# Docker
curl -fsSL https://get.docker.com | sh

# Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verificar
docker --version
docker-compose --version
```

**Versões mínimas:**
- Docker: 20.10+
- Docker Compose: 2.0+

---

## 🏗️ Arquitetura

```
┌─────────────────────────────────────────────────────┐
│                    DOCKER NETWORK                   │
│                  (ri-ifba-network)                  │
│                                                     │
│  ┌──────────┐   ┌──────────┐   ┌──────────────┐   │
│  │  NGINX   │──▶│   APP    │──▶│  POSTGRESQL  │   │
│  │ (Proxy)  │   │ (Laravel)│   │  (Database)  │   │
│  └──────────┘   └──────────┘   └──────────────┘   │
│      :80             :9000            :5432         │
│                         │                           │
│                         ▼                           │
│                   ┌──────────┐                      │
│                   │  REDIS   │                      │
│                   │ (Cache)  │                      │
│                   └──────────┘                      │
│                       :6379                         │
│                                                     │
│  ┌───────────────┐   ┌──────────────┐             │
│  │ QUEUE WORKER  │   │  SCHEDULER   │             │
│  │  (Async Jobs) │   │  (Cron Jobs) │             │
│  └───────────────┘   └──────────────┘             │
└─────────────────────────────────────────────────────┘
```

---

## 🚀 Instalação Rápida

### 1. Clone o Repositório
```bash
git clone https://github.com/seu-usuario/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
```

### 2. Configure o Ambiente
```bash
# Copiar arquivo de configuração
cp .env.docker .env

# Ou usar Makefile
make setup
```

### 3. Inicie os Containers
```bash
# Método 1: Docker Compose
docker-compose up -d

# Método 2: Makefile (recomendado)
make up
```

### 4. Acesse a Aplicação
```
✅ Backend: http://localhost:8000
✅ Adminer (BD): http://localhost:8080
```

### 5. Popular o Banco
```bash
# Via docker-compose
docker-compose exec app php artisan migrate:fresh --seed

# Via Makefile
make migrate-seed
```

---

## 🛠️ Comandos Principais

### Usando Docker Compose

```bash
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose down

# Ver logs
docker-compose logs -f

# Acessar shell da aplicação
docker-compose exec app bash

# Executar artisan
docker-compose exec app php artisan migrate

# Acessar PostgreSQL
docker-compose exec postgres psql -U postgres -d ri_ifba

# Reiniciar containers
docker-compose restart

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d
```

### Usando Makefile (Recomendado)

```bash
# Ver todos os comandos disponíveis
make help

# Setup inicial completo
make setup

# Iniciar
make up

# Parar
make down

# Reiniciar
make restart

# Logs
make logs
make logs-app
make logs-nginx

# Banco de dados
make migrate
make seed
make migrate-seed
make db-shell

# Testes
make test
make phpstan
make pint

# Cache
make cache-clear
make cache-optimize

# Informações
make info
make ps
```

---

## 💻 Desenvolvimento

### Estrutura de Arquivos Docker

```
ri_ifba_v1_backend/
├── docker/
│   ├── entrypoint.sh          # Script de inicialização
│   ├── nginx/
│   │   ├── nginx.conf         # Configuração principal Nginx
│   │   └── conf.d/
│   │       └── laravel.conf   # Virtual host Laravel
│   ├── php/
│   │   ├── php.ini            # PHP desenvolvimento
│   │   └── php-prod.ini       # PHP produção
│   └── postgres/
│       └── init/
│           └── 01-init.sql    # Script inicial do BD
├── Dockerfile                 # Imagem produção
├── Dockerfile.dev             # Imagem desenvolvimento
├── docker-compose.yml         # Compose desenvolvimento
├── docker-compose.prod.yml    # Compose produção
├── .dockerignore             # Arquivos ignorados
├── .env.docker               # Env desenvolvimento
└── .env.production.example   # Env produção
```

### Fluxo de Desenvolvimento

```bash
# 1. Iniciar ambiente
make up

# 2. Instalar dependências (se necessário)
make composer-install

# 3. Executar migrations
make migrate-seed

# 4. Acessar shell para trabalhar
make shell

# 5. Ver logs em tempo real
make logs-app

# 6. Executar testes
make test

# 7. Formatar código
make pint

# 8. Análise estática
make phpstan

# 9. Parar ambiente
make down
```

### Acessar Serviços

```bash
# Shell da aplicação
docker-compose exec app bash

# Shell do PostgreSQL
docker-compose exec postgres psql -U postgres -d ri_ifba

# Shell do Redis
docker-compose exec redis redis-cli -a redis

# Tinker
docker-compose exec app php artisan tinker
```

### Hot Reload

O Docker Compose de desenvolvimento usa **volumes** que mapeiam o código local para dentro do container:

```yaml
volumes:
  - ./:/var/www/html
```

Isso significa que **qualquer alteração** no código local é **refletida imediatamente** no container! 🎉

### Xdebug (Desenvolvimento)

O container de desenvolvimento inclui Xdebug configurado. Para usar:

1. Configure seu IDE (VSCode/PHPStorm)
2. Adicione breakpoints no código
3. Inicie debug remoto na porta 9003

---

## 🚀 Produção

### 1. Preparar Ambiente

```bash
# Copiar .env de produção
cp .env.production.example .env.production

# Editar variáveis sensíveis
nano .env.production
```

**⚠️ IMPORTANTE:** Altere estas variáveis:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=                              # Gerar nova key
APP_URL=https://seu-dominio.com.br

DB_PASSWORD=SENHA_FORTE_AQUI
REDIS_PASSWORD=SENHA_FORTE_AQUI

MAIL_HOST=seu-smtp.com
MAIL_USERNAME=seu-email
MAIL_PASSWORD=sua-senha
```

### 2. Build da Imagem

```bash
# Via docker-compose
docker-compose -f docker-compose.prod.yml build

# Via Makefile
make prod-build
```

### 3. Deploy

```bash
# Via docker-compose
docker-compose -f docker-compose.prod.yml up -d

# Via Makefile
make prod-deploy
```

### 4. Verificar

```bash
# Ver status
docker-compose -f docker-compose.prod.yml ps

# Ver logs
docker-compose -f docker-compose.prod.yml logs -f

# Via Makefile
make prod-ps
make prod-logs
```

### Diferenças Dev vs Prod

| Característica | Desenvolvimento | Produção |
|----------------|-----------------|----------|
| **Dockerfile** | Dockerfile.dev | Dockerfile |
| **Debug** | Xdebug habilitado | Desabilitado |
| **OPcache** | Desabilitado | Habilitado |
| **Composer** | Instala tudo | `--no-dev` |
| **Volumes** | Mapeamento local | Volume Docker |
| **Portas expostas** | PostgreSQL, Redis | Apenas HTTP/HTTPS |
| **Error reporting** | Completo | Mínimo |
| **Cache** | Desabilitado | Otimizado |

---

## 🗄️ Banco de Dados

### Conexão

**Dentro dos containers:**
```env
DB_HOST=postgres
DB_PORT=5432
```

**Do host (localhost):**
```env
DB_HOST=127.0.0.1
DB_PORT=5432
```

### Backup e Restore

```bash
# Backup automático
make db-backup

# Backup manual
docker-compose exec postgres pg_dump -U postgres ri_ifba > backup.sql

# Restore
make db-restore FILE=backup.sql

# Restore manual
docker-compose exec -T postgres psql -U postgres ri_ifba < backup.sql
```

### Adminer (GUI)

```bash
# Iniciar Adminer
make adminer-up

# Acessar: http://localhost:8080
# Server: postgres
# Username: postgres
# Password: postgres
# Database: ri_ifba
```

---

## 🔧 Configurações Avançadas

### Variáveis de Ambiente

#### `.env.docker` (Desenvolvimento)
```env
APP_PORT=8000          # Porta do Nginx
DB_PORT=5432           # Porta do PostgreSQL
REDIS_PORT=6379        # Porta do Redis
ADMINER_PORT=8080      # Porta do Adminer
USER_ID=1000           # UID do usuário
GROUP_ID=1000          # GID do grupo
```

### Personalizar PHP

Edite `docker/php/php.ini`:
```ini
memory_limit = 512M
upload_max_filesize = 50M
max_execution_time = 600
```

Reinicie:
```bash
make restart
```

### Personalizar Nginx

Edite `docker/nginx/conf.d/laravel.conf`:
```nginx
client_max_body_size 50M;
fastcgi_read_timeout 600;
```

Reinicie:
```bash
docker-compose restart nginx
```

### Adicionar Serviços

Edite `docker-compose.yml`:
```yaml
services:
  meu-servico:
    image: minha-imagem
    networks:
      - ri-ifba-network
```

---

## 🐛 Troubleshooting

### Problema: Porta já em uso

```bash
# Ver quem está usando a porta
netstat -ano | findstr :8000  # Windows
lsof -i :8000                 # Linux/Mac

# Mudar porta no .env
APP_PORT=8001
```

### Problema: Permissões no storage

```bash
# Dentro do container
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R laravel:laravel storage bootstrap/cache
```

### Problema: Container não inicia

```bash
# Ver logs detalhados
docker-compose logs app

# Rebuild sem cache
docker-compose build --no-cache app
docker-compose up -d
```

### Problema: Banco de dados não conecta

```bash
# Verificar se PostgreSQL está rodando
docker-compose ps postgres

# Ver logs do PostgreSQL
docker-compose logs postgres

# Testar conexão
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Problema: Redis não conecta

```bash
# Verificar Redis
docker-compose ps redis

# Testar conexão
docker-compose exec redis redis-cli -a redis ping
```

### Limpeza Completa

```bash
# Parar tudo
make down

# Remover volumes (⚠️ perde dados!)
make clean

# Limpeza total do Docker
make clean-all

# Recomeçar
make setup
```

---

## 📊 Monitoramento

### Verificar Status

```bash
# Status dos containers
make ps

# Uso de recursos
docker stats

# Logs em tempo real
make logs

# Informações do sistema
make info
```

### Health Checks

Todos os containers possuem health checks configurados:

```bash
# Ver health status
docker-compose ps

# Testar manualmente
curl http://localhost:8000/health
```

---

## 🔐 Segurança (Produção)

### Checklist de Segurança

- [ ] Alterar senhas padrão (DB, Redis)
- [ ] Configurar HTTPS/SSL
- [ ] Não expor portas desnecessárias
- [ ] Usar secrets do Docker
- [ ] Configurar firewall
- [ ] Manter imagens atualizadas
- [ ] Configurar logs centralizados
- [ ] Implementar backup automático
- [ ] Monitorar recursos
- [ ] Configurar rate limiting

### SSL/HTTPS

1. Obter certificados (Let's Encrypt)
2. Colocar em `docker/nginx/ssl/`
3. Configurar Nginx para HTTPS
4. Atualizar `APP_URL` no .env

---

## 📝 Comandos Úteis

```bash
# Ver todos os comandos
make help

# Informações do ambiente
make info

# Setup completo
make setup

# Deploy produção
make prod-deploy

# Testes + qualidade
make quality

# Backup banco
make db-backup

# Shell da aplicação
make shell

# Tinker
make tinker

# Limpar cache
make cache-clear

# Otimizar
make cache-optimize
```

---

## 🆘 Suporte

### Links Úteis

- [Docker Docs](https://docs.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Laravel Docs](https://laravel.com/docs)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)

### Comandos de Diagnóstico

```bash
# Versões
docker --version
docker-compose --version
make info

# Logs completos
docker-compose logs --tail=100

# Processos
docker-compose top

# Inspecionar container
docker inspect ri-ifba-app

# Uso de recursos
docker stats
```

---

## 📄 Licença

Este projeto está sob a licença MIT.

---

**Última atualização:** 13/01/2026  
**Versão Docker:** 1.0.0  
**Ambiente:** PHP 8.4 + Laravel 12 + PostgreSQL 16

