# 🚀 Guia Rápido: Como Clonar e Rodar o Projeto

## 📋 Pré-requisitos

Antes de começar, você precisa ter instalado:

- ✅ **Git** - Para clonar o repositório
- ✅ **Docker Desktop** - Para rodar o projeto
  - Download: https://www.docker.com/products/docker-desktop
  - **Importante:** Ativar integração WSL 2 (Settings → Resources → WSL Integration)

---

## 🎯 Passo a Passo Completo

### 1️⃣ Clonar o Repositório

```bash
# Abrir terminal (PowerShell, CMD ou WSL)
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git

# Entrar na pasta do projeto
cd ri_ifba_v1_backend
```

---

### 2️⃣ Resolver Permissão do Docker (Apenas WSL/Linux)

**⚠️ Se você estiver usando WSL/Linux, execute:**

```bash
# Adicionar seu usuário ao grupo docker
sudo usermod -aG docker $USER

# Aplicar mudanças
newgrp docker

# Verificar se funcionou
docker ps
```

**Se estiver no Windows (PowerShell/CMD), pule este passo!**

---

### 3️⃣ Rodar o Projeto (1 Comando!)

```bash
# Executar setup completo
make setup
```

**O que acontece automaticamente:**
- ✅ Copia `.env.docker` para `.env`
- ✅ Faz build de todos os containers
- ✅ Sobe 7 containers (Nginx, Laravel, PostgreSQL, Redis, etc)
- ✅ Gera `APP_KEY` automaticamente
- ✅ Instala dependências do Composer
- ✅ Executa migrations
- ✅ Popula banco com dados de teste (27 usuários)

**⏱️ Tempo:** 3-5 minutos na primeira vez

---

### 4️⃣ Acessar o Sistema

Após o setup concluir, acesse:

#### 🌐 API Backend:
```
http://localhost:8000
```

#### 🗄️ Adminer (Gerenciador de Banco):
```
http://localhost:8080
```

**Credenciais do Adminer:**
- Sistema: **PostgreSQL**
- Servidor: **postgres**
- Usuário: **postgres**
- Senha: **201099**
- Base de dados: **ri_ifba_v1**

---

## 🔑 Credenciais de Acesso (Usuários de Teste)

### 👨‍💼 Admin (Acesso Completo)
```
Matrícula: 10000000001
Senha: password
```

### 👨‍🎓 Bolsista (Exemplo)
```
Matrícula: 20231160001
Senha: password
```

Existem 20 bolsistas (matrículas de 20231160001 até 20231160020)

### 👤 Não Bolsista (Exemplo)
```
Matrícula: 20232160001
Senha: password
```

Existem 5 não bolsistas (matrículas de 20232160001 até 20232160005)

**📋 Lista completa:** Ver arquivo `CREDENCIAIS_ACESSO.md`

---

## 🧪 Testar a API

### Cardápio do Dia:
```bash
curl http://localhost:8000/api/v1/cardapio/hoje
```

### Login (Admin):
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"matricula":"10000000001","password":"password"}'
```

---

## 🛠️ Comandos Úteis

### Gerenciar Containers:
```bash
make up          # Iniciar containers
make down        # Parar containers
make restart     # Reiniciar containers
make logs        # Ver logs em tempo real
make ps          # Status dos containers
```

### Desenvolvimento:
```bash
make shell       # Acessar shell da aplicação
make tinker      # Laravel Tinker (console)
make test        # Executar testes
```

### Banco de Dados:
```bash
make migrate     # Executar migrations
make seed        # Popular dados de teste
make db-shell    # Acessar PostgreSQL
make db-backup   # Fazer backup
```

### Ver Todos os Comandos:
```bash
make help        # Lista completa (50+ comandos)
```

---

## 🔄 Comandos Alternativos (Sem Make)

Se você preferir não usar Make ou tiver problemas:

### Setup Manual:

```bash
# 1. Copiar .env
cp .env.docker .env

# 2. Build
docker compose build

# 3. Subir containers
docker compose up -d

# 4. Aguardar containers iniciarem (30 segundos)
sleep 30

# 5. Instalar dependências
docker compose exec app composer install

# 6. Gerar APP_KEY (opcional, já é automático)
docker compose exec app php artisan key:generate

# 7. Migrations + Seed
docker compose exec app php artisan migrate:fresh --seed
```

### Comandos do Dia a Dia:

```bash
# Iniciar
docker compose up -d

# Parar
docker compose down

# Ver logs
docker compose logs -f

# Acessar shell
docker compose exec app bash

# Executar artisan
docker compose exec app php artisan migrate
```

---

## ⚠️ Problemas Comuns

### ❌ Erro: "docker-compose: No such file or directory"

**Causa:** Docker não está instalado

**Solução:** Instalar Docker Desktop
- Download: https://www.docker.com/products/docker-desktop
- Ativar integração WSL 2
- **📖 Guia completo:** `INSTALACAO_DOCKER_WSL.md`

---

### ❌ Erro: "permission denied"

**Causa:** Usuário não tem permissão para acessar Docker (apenas WSL/Linux)

**Solução:**
```bash
sudo usermod -aG docker $USER
newgrp docker
```

**📖 Guia completo:** `ERRO_PERMISSAO_DOCKER.md`

---

### ❌ Porta já em uso (8000 ou 8080)

**Solução:** Mudar porta no `.env`

```bash
# Editar .env
nano .env

# Mudar:
APP_PORT=8001  # ou outra porta disponível

# Reiniciar
make restart
```

---

### ❌ Containers não sobem

**Solução:**

```bash
# 1. Parar tudo
docker compose down -v

# 2. Limpar
docker system prune -f

# 3. Recomeçar
make setup
```

---

## 📚 Documentação Disponível

| Arquivo | Descrição |
|---------|-----------|
| `README.md` | Documentação principal |
| `DOCKER_GUIDE.md` | Guia completo Docker (350+ linhas) |
| `DEPLOY_GUIDE.md` | Deploy em produção (450+ linhas) |
| `INSTALACAO_DOCKER_WSL.md` | Como instalar Docker |
| `ERRO_PERMISSAO_DOCKER.md` | Resolver erro de permissão |
| `CREDENCIAIS_ACESSO.md` | Lista de usuários de teste |
| `APP_KEY_AUTOMATICO.md` | Como funciona o APP_KEY |
| `CONFIGURACAO_BANCO.md` | Configuração do banco |
| `VERSOES_DEPENDENCIAS.md` | Stack e versões |

---

## 🎯 Resumo Ultra Rápido

```bash
# 1. Clonar
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# 2. Permissão (apenas WSL/Linux)
sudo usermod -aG docker $USER && newgrp docker

# 3. Rodar
make setup

# 4. Acessar
# http://localhost:8000 (API)
# http://localhost:8080 (Adminer)

# 5. Login
# Admin: 10000000001 / password
```

**Pronto!** 🎉

---

## 📊 Estrutura dos Containers

Quando rodar `make setup`, serão criados:

| Container | Função | Porta |
|-----------|--------|-------|
| **ri-ifba-nginx** | Servidor Web | 8000 |
| **ri-ifba-app** | Laravel (PHP-FPM) | - |
| **ri-ifba-postgres** | Banco de Dados | 5432 |
| **ri-ifba-redis** | Cache & Queue | 6379 |
| **ri-ifba-queue** | Queue Worker | - |
| **ri-ifba-adminer** | Gerenciador BD | 8080 |

---

## 🔍 Verificar se Está Funcionando

### Ver containers rodando:
```bash
make ps
# ou
docker compose ps
```

### Testar API:
```bash
curl http://localhost:8000/api/v1/cardapio/hoje
```

### Acessar Adminer:
Abrir navegador: http://localhost:8080

### Ver logs:
```bash
make logs
```

---

## 🎓 Próximos Passos

Depois de rodar o projeto:

1. **Explorar a API:**
   - Ver rotas disponíveis no `routes/api.php`
   - Testar endpoints no Postman ou via cURL

2. **Desenvolver:**
   - Editar código localmente
   - Hot reload funcionando (mudanças refletem automaticamente)
   - Ver logs: `make logs`

3. **Testar:**
   - Executar testes: `make test`
   - Formatar código: `make pint`
   - Análise estática: `make phpstan`

4. **Deploy:**
   - Seguir guia: `DEPLOY_GUIDE.md`
   - Build produção: `make prod-build`

---

## 💡 Dicas

### Atalhos úteis:

```bash
# Reiniciar rápido
make restart

# Ver logs apenas da aplicação
make logs-app

# Acessar Laravel Tinker
make tinker

# Executar migrations
make migrate

# Criar backup do banco
make db-backup
```

### Parar e limpar tudo:

```bash
# Parar containers
make down

# Parar e remover volumes (⚠️ perde dados)
make clean
```

---

## 🆘 Precisa de Ajuda?

1. **Ver logs:** `make logs`
2. **Consultar docs:** Ver arquivos `.md` na raiz
3. **Troubleshooting:** `DOCKER_GUIDE.md` seção "Troubleshooting"
4. **Comandos:** `make help`

---

## ✅ Checklist Final

Após clonar e rodar, verifique:

- [ ] Containers estão rodando (`make ps`)
- [ ] API responde (`curl http://localhost:8000`)
- [ ] Adminer acessível (`http://localhost:8080`)
- [ ] Login funciona (admin: 10000000001 / password)
- [ ] Banco populado (27 usuários)

**Se tudo estiver ✅, você está pronto para desenvolver!** 🚀

---

**Última atualização:** 13/01/2026  
**Versão:** 1.0.0  
**Stack:** PHP 8.4 + Laravel 12 + PostgreSQL 16 + Redis 7 + Docker

