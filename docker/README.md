# 📁 Estrutura de Arquivos Docker

Este diretório contém todos os arquivos de configuração Docker do projeto.

## 📋 Arquivos Principais

### Dockerfiles
- `Dockerfile` - Imagem de produção otimizada
- `Dockerfile.dev` - Imagem de desenvolvimento com Xdebug

### Docker Compose
- `docker-compose.yml` - Ambiente de desenvolvimento
- `docker-compose.prod.yml` - Ambiente de produção

### Configurações
- `.env.docker` - Variáveis de ambiente (desenvolvimento)
- `.env.production.example` - Template para produção
- `.dockerignore` - Arquivos ignorados no build
- `Makefile` - Comandos facilitados

## 📂 Diretório docker/

### entrypoint.sh
Script de inicialização executado quando o container sobe:
- Aguarda PostgreSQL ficar pronto
- Gera APP_KEY se necessário
- Limpa caches
- Executa migrations
- Configura permissões

### nginx/
Configurações do servidor web Nginx:
- `nginx.conf` - Configuração principal
- `conf.d/laravel.conf` - Virtual host para Laravel
- `ssl/` - Certificados SSL (não versionado)

### php/
Configurações PHP:
- `php.ini` - Desenvolvimento (debug ativo)
- `php-prod.ini` - Produção (otimizado)

### postgres/
Scripts de inicialização do PostgreSQL:
- `init/01-init.sql` - Cria extensões e configura timezone

## 🚀 Como Usar

### Desenvolvimento
```bash
make setup   # Setup inicial
make up      # Iniciar
make down    # Parar
make logs    # Ver logs
```

### Produção
```bash
cp .env.production.example .env.production
# Editar senhas
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d
```

## 📚 Documentação

- `DOCKER_GUIDE.md` - Guia completo
- `DEPLOY_GUIDE.md` - Deploy em produção
- `README.md` - Documentação do projeto

## 🔐 Segurança

**⚠️ IMPORTANTE:**
- Nunca commite arquivos `.env` com senhas reais
- Altere todas as senhas padrão em produção
- Use `.env.production.example` como template

## 📊 Containers

| Container | Porta | Descrição |
|-----------|-------|-----------|
| nginx | 8000 | Servidor web |
| app | 9000 | Laravel (PHP-FPM) |
| postgres | 5432 | Banco de dados |
| redis | 6379 | Cache & Queue |
| adminer | 8080 | Gerenciador BD |

## ✅ Health Checks

Todos os containers possuem health checks configurados:
```bash
docker-compose ps  # Ver status de health
```

## 🐛 Troubleshooting

Ver seção de troubleshooting em `DOCKER_GUIDE.md`

---

**Última atualização:** 13/01/2026

