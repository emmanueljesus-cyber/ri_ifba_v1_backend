# 🐳 Docker e Banco de Dados - Guia Completo

## 📋 Índice
1. [🚀 INÍCIO RÁPIDO - Clone e Rode em 5 Minutos](#inicio-rapido)
2. [Como o Docker Funciona Neste Projeto](#como-funciona)
3. [Banco de Dados Automático](#banco-automatico)
4. [Por Que Não Funcionou Após Baixar?](#troubleshooting)
5. [Como Rodar em Máquina Nova](#setup-nova-maquina)
6. [Credenciais e Variáveis de Ambiente](#credenciais)
7. [FAQ](#faq)

---

## 🚀 INÍCIO RÁPIDO - Clone e Rode em 5 Minutos {#inicio-rapido}

### ⚡ Passo a Passo Completo (Copy & Paste)

#### ✅ Pré-requisitos (Verifique Antes):

1. **Docker Desktop rodando no Windows**
   - Abra o Docker Desktop
   - Aguarde aparecer "Docker Desktop is running" (ícone verde)

2. **WSL2 configurado**
   - Abra o terminal WSL (Ubuntu)

---

### 📝 PASSO 1: Configurar Permissões do Docker (Apenas 1ª Vez)

**⚠️ IMPORTANTE:** Você precisa de permissão `sudo` apenas para configurar pela primeira vez. Depois disso, não precisa mais!

```bash
# Adicionar seu usuário ao grupo docker
sudo usermod -aG docker $USER

# Recarregar grupos (SEM precisar fazer logout)
newgrp docker

# Testar se funcionou (NÃO precisa mais de sudo)
docker ps
```

**✅ Se mostrar uma tabela** (mesmo que vazia), funcionou!  
**❌ Se der "permission denied"**, reinicie o WSL:
```bash
# No PowerShell do Windows
wsl --shutdown

# Aguarde 5 segundos e abra o WSL novamente
wsl
```

---

### 📝 PASSO 2: Clonar o Projeto

```bash
# Navegue para onde quer guardar o projeto
cd ~
# ou
cd /mnt/c/Users/SEU_USUARIO/Documents

# Clone o repositório
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git

# Entre na pasta
cd ri_ifba_v1_backend

# Verifique se está tudo OK
ls -la
```

**✅ Deve mostrar:** `Makefile`, `docker-compose.yml`, `artisan`, etc.

---

### 📝 PASSO 3: Subir os Containers (Comando Único!)

```bash
# Comando mágico que faz TUDO automaticamente
make setup
```

**🎯 O que este comando faz automaticamente:**

1. ✅ Copia `.env.docker` → `.env` (configurações Docker)
2. ✅ Constrói imagem Docker (PHP 8.4 + extensões)
3. ✅ Sobe 5 containers (Nginx, Laravel, PostgreSQL, Redis, Queue)
4. ✅ Instala dependências PHP (Composer)
5. ✅ Gera `APP_KEY` automaticamente
6. ✅ Cria banco de dados `ri_ifba_v1`
7. ✅ Executa migrations (cria 20+ tabelas)
8. ✅ Executa seeders (cria 26 usuários de teste)
9. ✅ Limpa cache

**⏱️ Tempo:** 5-10 minutos (primeira vez)

**💡 Você verá mensagens como:**
```
🚀 Configurando projeto...
docker compose build
[+] Building ...
🔧 Instalando dependências PHP...
⚙️ Preparando ambiente...
🔑 Gerando APP_KEY...
📊 Executando migrations...
🌱 Populando banco de dados...
✅ Setup concluído!
```

---

### 📝 PASSO 4: Verificar se Está Tudo Funcionando

```bash
# Ver status dos containers
docker compose ps
```

**✅ SUCESSO - Todos devem estar "Up":**
```
NAME               STATUS
ri-ifba-app        Up 2 minutes
ri-ifba-nginx      Up 2 minutes
ri-ifba-postgres   Up 2 minutes (healthy)
ri-ifba-redis      Up 2 minutes (healthy)
ri-ifba-queue      Up 2 minutes
```

**❌ ERRO - Se algum estiver "Restarting" ou "Exited":**
```bash
# Ver logs do container com problema
docker compose logs nome-do-container

# Exemplo:
docker compose logs nginx
docker compose logs queue-worker
```

➡️ **Se houver erro, veja a seção [Troubleshooting](#troubleshooting) abaixo**

---

### 📝 PASSO 5: Testar a API

```bash
# Testar rota pública (cardápio do dia)
curl http://localhost:8000/api/v1/cardapio/hoje
```

**✅ SUCESSO - Deve retornar JSON:**
```json
{
  "data": {...},
  "errors": [],
  "meta": {
    "timestamp": "2026-01-13T10:30:00Z"
  }
}
```

**❌ ERRO - "Failed to connect":**
- Nginx não está rodando
- Veja logs: `docker compose logs nginx`

---

### 📝 PASSO 6: Acessar no Navegador

Abra no seu navegador:

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **Backend API** | http://localhost:8000 | - |
| **Adminer (Banco)** | http://localhost:8080 | Ver abaixo ↓ |

**Credenciais do Adminer:**
```
Sistema: PostgreSQL
Servidor: postgres
Usuário: postgres
Senha: 201099
Base de dados: ri_ifba_v1
```

---

### 🎉 PRONTO! Projeto Rodando!

**Credenciais de Acesso (Login na API):**

| Perfil | Matrícula | Senha | Acesso |
|--------|-----------|-------|--------|
| **Admin** | 10000000001 | password | `/api/v1/admin/*` |
| **Bolsista** | 20231160001 | password | `/api/v1/estudante/bolsista/*` |
| **Não-Bolsista** | 20232160001 | password | `/api/v1/estudante/nao-bolsista/*` |

**Teste o login:**
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"matricula":"10000000001","password":"password"}'
```

---

### 🔄 Comandos Úteis (Dia a Dia)

**⚠️ IMPORTANTE:** Após configurar permissões (Passo 1), você **NÃO precisa mais usar `sudo`**!

```bash
# Ver containers rodando
docker compose ps

# Ver logs em tempo real
docker compose logs -f app

# Parar todos os containers
docker compose down

# Subir novamente (rápido - já está buildado)
docker compose up -d

# Reiniciar um container específico
docker compose restart nginx

# Acessar shell do container Laravel
docker compose exec app bash

# Rodar comandos Laravel
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Ver uso de recursos
docker stats

# Limpar tudo e recomeçar (CUIDADO: apaga banco!)
docker compose down -v
make setup
```

---

### ❓ Preciso Usar `sudo` Sempre?

**NÃO!** ❌

| Situação | Comando | Precisa `sudo`? |
|----------|---------|-----------------|
| **Primeira configuração** | `sudo usermod -aG docker $USER` | ✅ SIM (só 1 vez) |
| **Recarregar grupos** | `newgrp docker` | ❌ NÃO |
| **Depois de configurado** | `docker compose up` | ❌ NÃO |
| **Comandos make** | `make setup`, `make up`, `make down` | ❌ NÃO |
| **Ver containers** | `docker compose ps` | ❌ NÃO |
| **Ver logs** | `docker compose logs` | ❌ NÃO |

**Resumo:**
- ✅ **Use `sudo` apenas UMA VEZ** para adicionar seu usuário ao grupo docker
- ❌ **Depois disso, NUNCA mais precisa de `sudo`**
- ⚠️ **Se pedir `sudo` após configurado, algo está errado** (veja [Troubleshooting](#troubleshooting))

---

### 🐛 Problemas Comuns (Resolução Rápida)

#### ❌ "permission denied" (mesmo após usermod)

**Causa:** Grupo docker não foi recarregado.

**Solução:**
```bash
# Recarregar grupos
newgrp docker

# OU reiniciar WSL (no PowerShell do Windows)
wsl --shutdown
# Aguarde 5 segundos
wsl
```

---

#### ❌ "no configuration file provided: not found" (Nginx)

**Causa:** Arquivos `nginx.conf` corrompidos.

**Solução:**
```bash
# Parar containers
docker compose down

# Verificar se é um arquivo válido
file docker/nginx/nginx.conf

# Se for diretório ou der erro, restaurar do Git
rm -rf docker/nginx/nginx.conf docker/nginx/conf.d/laravel.conf
git checkout docker/nginx/nginx.conf docker/nginx/conf.d/laravel.conf

# Subir novamente
docker compose up -d
```

---

#### ❌ Queue Worker reiniciando ("Undefined table: cache")

**Causa:** `.env` com `CACHE_DRIVER=database` em vez de `redis`.

**Solução:**
```bash
# Parar containers
docker compose down

# Copiar configuração correta
cp .env.docker .env

# Verificar
grep "CACHE_DRIVER" .env
# Deve mostrar: CACHE_DRIVER=redis

# Subir novamente
docker compose up -d
```

---

#### ❌ "Clock skew detected"

**Causa:** Relógio do WSL dessincronizado.

**Solução:**
```bash
sudo hwclock -s
```

---

#### ❌ "Failed to connect to localhost port 8000"

**Causa:** Nginx não está rodando.

**Solução:**
```bash
# Ver status
docker compose ps

# Se nginx não está "Up", ver logs
docker compose logs nginx

# Reiniciar nginx
docker compose restart nginx

# Aguardar 5 segundos e testar
curl http://localhost:8000/api/v1/cardapio/hoje
```

---

### 📚 Próximos Passos

✅ **Projeto rodando?** Excelente! Agora você pode:

1. **Desenvolver:** Edite os arquivos e as mudanças aparecem automaticamente
2. **Testar rotas:** Use Postman, Insomnia ou curl
3. **Ver banco de dados:** Acesse http://localhost:8080 (Adminer)
4. **Ler documentação:** Veja os arquivos `.md` na raiz do projeto

**Documentação recomendada:**
- `DOCKER_GUIDE.md` - Comandos avançados
- `README.md` - Visão geral do projeto
- `CREDENCIAIS_ACESSO.md` - Lista completa de usuários
- `routes/api.php` - Rotas disponíveis

---

## 🐳 Como o Docker Funciona Neste Projeto {#como-funciona}

### Arquitetura de Containers

O projeto usa **5 containers Docker** que trabalham juntos:

```
┌─────────────────────────────────────────────────┐
│                   DOCKER HOST                   │
│                                                 │
│  ┌──────────────┐     ┌──────────────┐         │
│  │    NGINX     │────▶│  Laravel App │         │
│  │ (Porta 8000) │     │  (PHP 8.4)   │         │
│  └──────────────┘     └──────┬───────┘         │
│                              │                  │
│  ┌──────────────┐     ┌──────▼───────┐         │
│  │  PostgreSQL  │◀────│Queue Worker  │         │
│  │ (Porta 5432) │     │   (Laravel)  │         │
│  └──────────────┘     └──────┬───────┘         │
│                              │                  │
│           ┌──────────────────▼─┐                │
│           │      Redis         │                │
│           │ (Cache & Queue)    │                │
│           └────────────────────┘                │
└─────────────────────────────────────────────────┘
```

### O Que Cada Container Faz:

| Container | Imagem | Função | Porta |
|-----------|--------|--------|-------|
| **nginx** | `nginx:1.25-alpine` | Servidor web (proxy reverso) | 8000 |
| **app** | `ri-ifba-backend:dev` | Aplicação Laravel (PHP-FPM) | 9000 |
| **postgres** | `postgres:16.11-alpine` | Banco de dados PostgreSQL | 54320 |
| **redis** | `redis:7-alpine` | Cache e fila de jobs | 6379 |
| **queue-worker** | `ri-ifba-backend:dev` | Processador de filas (Laravel) | - |

---

## 🗄️ Banco de Dados Automático {#banco-automatico}

### ✅ Sim, o Docker Cria o Banco Automaticamente!

Quando você executa `make setup`, o Docker faz **TUDO automaticamente**:

#### 1️⃣ **Criação do Container PostgreSQL**
```yaml
# docker-compose.yml
postgres:
  image: postgres:16.11-alpine
  environment:
    POSTGRES_DB: ri_ifba_v1        # ← Banco criado automaticamente
    POSTGRES_USER: postgres
    POSTGRES_PASSWORD: 201099
```

#### 2️⃣ **Configuração Automática das Variáveis de Ambiente**
O arquivo `.env.docker` é copiado para `.env` automaticamente:
```env
DB_CONNECTION=pgsql
DB_HOST=postgres          # ← Nome do container (DNS interno)
DB_PORT=5432              # ← Porta interna do container
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

#### 3️⃣ **Geração Automática do APP_KEY**
O script `docker/entrypoint.sh` gera automaticamente:
```bash
if [ -z "$APP_KEY_VALUE" ]; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate --force
fi
```

#### 4️⃣ **Execução das Migrations**
Cria todas as tabelas do banco:
```bash
php artisan migrate --force
```

#### 5️⃣ **População com Dados de Teste (Seeders)**
Cria 26 usuários automaticamente:
```bash
php artisan db:seed --force
```

### 📦 O Que é Criado Automaticamente:

✅ **Container PostgreSQL** rodando  
✅ **Banco de dados** `ri_ifba_v1` criado  
✅ **Tabelas** criadas via migrations  
✅ **26 usuários** cadastrados (1 admin + 20 bolsistas + 5 não-bolsistas)  
✅ **APP_KEY** gerado  
✅ **Redis** configurado para cache e filas  

---

## ❌ Por Que Não Funcionou Após Baixar? {#troubleshooting}

### Problema Identificado:

Você executou `make setup` mas encontrou erros. Vamos analisar os possíveis motivos:

### 1️⃣ **Permissões do Docker (WSL)**

**Erro:**
```
permission denied while trying to connect to the Docker daemon socket
```

**Causa:** Seu usuário no WSL não tem permissão para usar o Docker.

**Solução:**
```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Recarregar grupos (sem precisar fazer logout)
newgrp docker

# Verificar se funcionou
docker ps
```

### 2️⃣ **Docker Desktop Não Estava Rodando**

**Causa:** Docker Desktop precisa estar aberto no Windows.

**Solução:**
1. Abra o Docker Desktop no Windows
2. Aguarde aparecer "Docker Desktop is running"
3. Tente novamente: `make setup`

### 3️⃣ **Clock Skew (Diferença de Horário)**

**Aviso:**
```
make: warning: Clock skew detected. Your build may be incomplete.
```

**Causa:** Relógio do WSL está dessincronizado do Windows.

**Solução:**
```bash
# Sincronizar relógio do WSL
sudo hwclock -s
```

### 4️⃣ **Espaço em Disco Insuficiente**

**Erro:**
```
Less than 1 MiB is left on the system directory partition (C:)
```

**Causa:** Disco C: cheio.

**Solução:**
1. Libere espaço no disco C:
2. Limpe cache do Docker:
```bash
docker system prune -af --volumes
```

### 5️⃣ **Containers Conflitantes**

**Causa:** Containers antigos ainda rodando.

**Solução:**
```bash
# Parar todos os containers do projeto
docker compose down -v

# Limpar imagens antigas
docker image rm ri-ifba-backend:dev || true

# Executar setup novamente
make setup
```

### 6️⃣ **Nginx: "no configuration file provided: not found"**

**Erro:**
```
ri-ifba-nginx  | no configuration file provided: not found
```

**Causa:** Arquivos de configuração do Nginx não foram encontrados ou estão corrompidos.

**Diagnóstico:**
```bash
# Verificar se os arquivos existem
ls -la docker/nginx/nginx.conf
ls -la docker/nginx/conf.d/laravel.conf

# Deve mostrar ARQUIVOS, não diretórios
```

**Solução:**
```bash
# Parar containers
docker compose down

# Verificar se nginx.conf é um arquivo válido
file docker/nginx/nginx.conf
# Deve retornar: "ASCII text" ou similar

# Se for um diretório ou estiver corrompido, deletar e recriar
rm -rf docker/nginx/nginx.conf docker/nginx/conf.d/laravel.conf

# Opção 1: Baixar do repositório
git checkout docker/nginx/nginx.conf docker/nginx/conf.d/laravel.conf

# Opção 2: Recriar manualmente (veja seção "Configurações Nginx" abaixo)

# Subir novamente
docker compose up -d
```

**Verificar se resolveu:**
```bash
# Ver logs do nginx
docker compose logs nginx

# Deve mostrar:
# "Configuration complete; ready for start up"
# ou similar

# Testar API
curl http://localhost:8000/api/v1/cardapio/hoje
```

### 7️⃣ **Queue Worker: "Undefined table: cache"**

**Erro:**
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "cache" does not exist
```

**Causa:** O `.env` está configurado com `CACHE_DRIVER=database` mas deveria usar Redis.

**Solução:**
```bash
# Parar containers
docker compose down

# Verificar configuração do cache no .env
grep -E "(CACHE_DRIVER|SESSION_DRIVER|QUEUE_CONNECTION)" .env

# Deve mostrar:
# CACHE_DRIVER=redis
# SESSION_DRIVER=redis
# QUEUE_CONNECTION=redis

# Se estiver diferente, editar .env:
sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=redis/' .env
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env

# Ou simplesmente copiar de novo:
cp .env.docker .env

# Subir novamente
docker compose up -d

# Verificar se queue-worker está funcionando
docker compose ps
# Status deve ser "Up" (não "Restarting")
```

---

## 🔧 Configurações Nginx (Caso Precise Recriar)

Se os arquivos de configuração do Nginx estiverem corrompidos, você pode recriá-los:

### docker/nginx/nginx.conf

```nginx
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 20M;

    gzip on;
    gzip_disable "msie6";

    include /etc/nginx/conf.d/*.conf;
}
```

### docker/nginx/conf.d/laravel.conf

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Como usar:**
1. Crie o arquivo `docker/nginx/nginx.conf` com o conteúdo acima
2. Crie o arquivo `docker/nginx/conf.d/laravel.conf` com o conteúdo acima
3. Execute: `docker compose restart nginx`

---

## 🚀 Como Rodar em Máquina Nova {#setup-nova-maquina}

### Pré-requisitos:

✅ **Windows 10/11** com WSL2 instalado  
✅ **Docker Desktop** instalado e rodando  
✅ **Git** instalado  

### Passo a Passo Completo:

#### 1️⃣ **Configurar Docker Desktop (Windows)**

1. Instale [Docker Desktop](https://www.docker.com/products/docker-desktop)
2. Abra Docker Desktop
3. Vá em **Settings → General**
4. ✅ Ative: **Use the WSL 2 based engine**
5. Vá em **Settings → Resources → WSL Integration**
6. ✅ Ative integração com sua distribuição WSL (Ubuntu)
7. Clique em **Apply & Restart**

#### 2️⃣ **Configurar Permissões no WSL**

Abra o terminal WSL (Ubuntu):

```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Recarregar grupos
newgrp docker

# Verificar se funciona
docker ps
```

**✅ Deve mostrar:** "CONTAINER ID   IMAGE   ..."  
**❌ Se der erro:** Reinicie o WSL ou faça logout/login

#### 3️⃣ **Clonar o Repositório**

```bash
# Navegue até a pasta desejada
cd ~/projetos  # ou qualquer pasta

# Clone o repositório
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
```

#### 4️⃣ **Executar Setup Automático**

```bash
make setup
```

**O que acontece (automaticamente):**
1. ✅ Copia `.env.docker` para `.env`
2. ✅ Constrói imagens Docker (Laravel + PHP 8.4)
3. ✅ Sobe 5 containers (Nginx, Laravel, PostgreSQL, Redis, Queue)
4. ✅ Instala dependências PHP (Composer)
5. ✅ Gera APP_KEY
6. ✅ Executa migrations (cria tabelas)
7. ✅ Executa seeders (popula 26 usuários)
8. ✅ Limpa cache

**Tempo estimado:** 5-10 minutos (primeira vez)

#### 5️⃣ **Verificar se Está Funcionando**

```bash
# Ver status dos containers
docker compose ps
```

**✅ Todos devem estar com status "Up"**:
```
NAME               STATUS
ri-ifba-app        Up 2 minutes
ri-ifba-nginx      Up 2 minutes
ri-ifba-postgres   Up 2 minutes (healthy)
ri-ifba-redis      Up 2 minutes (healthy)
ri-ifba-queue      Up 2 minutes
```

#### 6️⃣ **Testar API**

```bash
# Testar cardápio do dia (rota pública)
curl http://localhost:8000/api/v1/cardapio/hoje

# Deve retornar JSON: {"data": ..., "errors": [], "meta": {...}}
```

#### 7️⃣ **Acessar no Navegador**

- **Backend API:** http://localhost:8000
- **Adminer (Gerenciador BD):** http://localhost:8080
  - **Sistema:** PostgreSQL
  - **Servidor:** postgres
  - **Usuário:** postgres
  - **Senha:** 201099
  - **Base de dados:** ri_ifba_v1

---

## 🔑 Credenciais e Variáveis de Ambiente {#credenciais}

### Credenciais do Banco (Docker)

Estas credenciais são definidas no `docker-compose.yml` e **NÃO devem ser alteradas** a menos que você saiba o que está fazendo:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres          # ← Nome do container (DNS interno)
DB_PORT=5432              # ← Porta interna (dentro da rede Docker)
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

### ⚠️ ATENÇÃO: Diferença Entre Portas

| Tipo | Porta | Uso |
|------|-------|-----|
| **Interna (container)** | 5432 | Comunicação entre containers |
| **Externa (host)** | 54320 | Acesso do Windows/WSL ao banco |

**Por que isso?**
- **Porta 5432 interna:** Laravel dentro do Docker se conecta ao PostgreSQL
- **Porta 54320 externa:** Você pode conectar do Windows (DBeaver, pgAdmin, etc)

### Conectar do Windows (DBeaver/pgAdmin):

```
Host: localhost
Port: 54320          ← DIFERENTE da interna!
Database: ri_ifba_v1
Username: postgres
Password: 201099
```

### Usuários de Teste (após seeder):

| Perfil | Matrícula | Senha | Quantidade |
|--------|-----------|-------|------------|
| **Admin** | 10000000001 | password | 1 |
| **Bolsistas** | 20231160001-020 | password | 20 |
| **Não-Bolsistas** | 20232160001-005 | password | 5 |
| **Pendentes** | 20231160021-025 | - | 5 (para teste RF01) |

---

## ❓ FAQ - Perguntas Frequentes {#faq}

### ❓ Preciso usar `sudo` para todos os comandos Docker?

❌ **NÃO!** Você só precisa de `sudo` **UMA ÚNICA VEZ** para configurar as permissões:

```bash
# Apenas na PRIMEIRA vez:
sudo usermod -aG docker $USER
newgrp docker
```

**Depois disso, todos os comandos funcionam SEM `sudo`:**
```bash
docker ps                    # ✅ SEM sudo
docker compose up -d         # ✅ SEM sudo
docker compose logs app      # ✅ SEM sudo
make setup                   # ✅ SEM sudo
make up                      # ✅ SEM sudo
```

**⚠️ Se pedir `sudo` após configurado:**
1. Grupo docker não foi recarregado → rode: `newgrp docker`
2. WSL precisa ser reiniciado → no PowerShell: `wsl --shutdown` e reabra

### ❓ O Docker cria o banco automaticamente?

✅ **SIM!** Quando você executa `make setup`, o Docker:
1. Cria o container PostgreSQL
2. Cria o banco de dados `ri_ifba_v1`
3. Executa as migrations (cria tabelas)
4. Executa os seeders (popula usuários de teste)

### ❓ O APP_KEY é gerado automaticamente?

✅ **SIM!** O script `docker/entrypoint.sh` detecta se o APP_KEY está vazio e gera automaticamente ao subir o container.

### ❓ Preciso configurar o .env manualmente?

❌ **NÃO!** O `make setup` copia automaticamente o `.env.docker` para `.env` com todas as configurações corretas.

### ❓ Por que deu erro após clonar o projeto?

Possíveis causas:
1. ❌ Docker Desktop não está rodando
2. ❌ Usuário não tem permissão (rode: `sudo usermod -aG docker $USER && newgrp docker`)
3. ❌ Containers antigos conflitando (rode: `docker compose down -v`)
4. ❌ Disco C: cheio (libere espaço)

### ❓ Como conectar no banco do Windows (DBeaver)?

Use estas credenciais:
```
Host: localhost
Port: 54320          ← Porta EXTERNA (não 5432!)
Database: ri_ifba_v1
Username: postgres
Password: 201099
```

### ❓ Qual a diferença entre porta 5432 e 54320?

| Porta | Contexto | Uso |
|-------|----------|-----|
| **5432** | Dentro do Docker | Laravel → PostgreSQL |
| **54320** | Fora do Docker | Windows/WSL → PostgreSQL |

**No `.env` use sempre 5432** (comunicação interna entre containers).

### ❓ Como rodar comandos Laravel dentro do Docker?

```bash
# Acessar shell do container
make shell

# Ou executar comando diretamente
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
```

### ❓ Preciso ter PostgreSQL instalado no Windows?

❌ **NÃO!** O PostgreSQL roda dentro do container Docker. Você **não precisa** instalar PostgreSQL no Windows.

### ❓ E se eu quiser usar minhas credenciais de banco?

Se você quiser conectar a um banco PostgreSQL **externo** (fora do Docker):

1. **Edite o `.env`** (não o `.env.docker`):
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1        # ← Localhost do Windows
DB_PORT=5432
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=SUA_SENHA_AQUI
```

2. **Remova o container PostgreSQL** do `docker-compose.yml`:
```yaml
# Comente ou remova a seção postgres:
# postgres:
#   image: postgres:16.11-alpine
#   ...
```

3. **Crie o banco manualmente** no PostgreSQL instalado no Windows

4. **Execute migrations**:
```bash
wsl php artisan migrate --seed
```

### ❓ Como fazer backup do banco?

```bash
# Dentro do WSL
make db-backup

# Ou manualmente:
docker compose exec postgres pg_dump -U postgres ri_ifba_v1 > backup.sql
```

### ❓ Como restaurar um backup?

```bash
# Restaurar do arquivo backup.sql
docker compose exec -T postgres psql -U postgres ri_ifba_v1 < backup.sql
```

---

## 🎯 Resumo Executivo

### ✅ Ambiente Docker (Recomendado):

| Item | Status | Detalhes |
|------|--------|----------|
| **Banco de dados** | ✅ Automático | PostgreSQL criado automaticamente |
| **APP_KEY** | ✅ Automático | Gerado pelo entrypoint.sh |
| **Migrations** | ✅ Automático | Executadas no setup |
| **Seeders** | ✅ Automático | 26 usuários criados |
| **Redis** | ✅ Automático | Cache e filas configurados |
| **Nginx** | ✅ Automático | Servidor web na porta 8000 |

**Comando único:** `make setup` → **Tudo pronto!** 🎉

### ⚙️ Instalação Manual (WSL):

| Item | Status | Ação Necessária |
|------|--------|-----------------|
| **Banco de dados** | ❌ Manual | Instalar PostgreSQL no Windows |
| **APP_KEY** | ❌ Manual | `php artisan key:generate` |
| **Migrations** | ❌ Manual | `php artisan migrate` |
| **Seeders** | ❌ Manual | `php artisan db:seed` |
| **Composer** | ❌ Manual | `composer install` |

**Mais trabalhoso** e sujeito a erros de ambiente.

---

## 📚 Documentação Relacionada

- **[DOCKER_GUIDE.md](DOCKER_GUIDE.md)** - Guia completo de uso do Docker (comandos, troubleshooting)
- **[DEPLOY_GUIDE.md](DEPLOY_GUIDE.md)** - Deploy em produção com Docker
- **[CONFIGURACAO_BANCO.md](CONFIGURACAO_BANCO.md)** - Detalhes sobre conexão com banco
- **[APP_KEY_AUTOMATICO.md](APP_KEY_AUTOMATICO.md)** - Como funciona a geração automática
- **[CREDENCIAIS_ACESSO.md](CREDENCIAIS_ACESSO.md)** - Lista completa de usuários de teste

---

## 🆘 Precisa de Ajuda?

### 🔍 Diagnóstico Rápido:

```bash
# Ver status dos containers
docker compose ps

# Ver logs de erros
docker compose logs app
docker compose logs postgres

# Ver informações do sistema
make info

# Ver TODOS os comandos disponíveis
make help
```

### 🐛 Problemas Comuns:

| Problema | Solução |
|----------|---------|
| Permissão negada | `sudo usermod -aG docker $USER && newgrp docker` |
| Containers não sobem | `docker compose down -v && make setup` |
| Porta 8000 ocupada | `make down && make up` |
| Banco não conecta | Verifique se `postgres` container está "healthy" |
| Clock skew | `sudo hwclock -s` |

---

**Última atualização:** Janeiro 2026  
**Desenvolvido com ❤️ para o IFBA**

