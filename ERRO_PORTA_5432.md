# ⚠️ Erro: Porta 5432 já está em uso

## 🔴 Problema

```bash
Error response from daemon: failed to set up container networking: 
driver failed programming external connectivity on endpoint ri-ifba-postgres: 
failed to bind host port 0.0.0.0:5432/tcp: address already in use
```

## 🎯 O que significa?

A porta **5432** (porta padrão do PostgreSQL) já está sendo usada pelo PostgreSQL instalado localmente no Windows.

**Estrutura:**

```
┌─────────────────────────────────────────┐
│           WINDOWS                       │
├─────────────────────────────────────────┤
│                                         │
│  ❌ PostgreSQL Local (Windows)          │
│     └─ Porta 5432 (OCUPADA!)           │
│                                         │
│  ┌──────────────────────────────────┐  │
│  │         DOCKER (WSL)             │  │
│  ├──────────────────────────────────┤  │
│  │                                  │  │
│  │  ❌ PostgreSQL Container          │  │
│  │     └─ Tentou usar porta 5432    │  │
│  │        ⚠️ CONFLITO!               │  │
│  │                                  │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

## ✅ Solução Aplicada

Configuramos o PostgreSQL do Docker para usar a porta **54320** (externa), evitando conflito:

```
┌─────────────────────────────────────────┐
│           WINDOWS                       │
├─────────────────────────────────────────┤
│                                         │
│  ✅ PostgreSQL Local (Windows)          │
│     └─ Porta 5432                      │
│                                         │
│  ┌──────────────────────────────────┐  │
│  │         DOCKER (WSL)             │  │
│  ├──────────────────────────────────┤  │
│  │                                  │  │
│  │  ✅ PostgreSQL Container          │  │
│  │     ├─ Porta interna: 5432       │  │
│  │     └─ Porta externa: 54320      │  │
│  │        ✅ SEM CONFLITO!            │  │
│  │                                  │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

## 🔧 O que foi alterado?

### 1. `.env.docker`

```dotenv
# Porta interna (dentro do Docker)
DB_PORT=5432

# Porta externa (para acessar do Windows)
DB_PORT_EXTERNAL=54320
```

### 2. `docker-compose.yml`

```yaml
postgres:
  ports:
    - "${DB_PORT_EXTERNAL:-54320}:5432"  # Windows:54320 → Container:5432
```

## 🚀 Como usar agora?

### Passo 1: Limpar containers antigos

```bash
cd "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend"
sudo docker compose down -v
```

### Passo 2: Iniciar o projeto

```bash
sudo make setup
```

**Ou manualmente:**

```bash
sudo docker compose up -d
```

## 🔌 Acessando os bancos

### PostgreSQL Local (Windows)

```
Host: localhost
Porta: 5432
Banco: ri_ifba_v1 (seu banco local)
Usuário: postgres
Senha: 201099
```

### PostgreSQL Docker (Projeto)

```
Host: localhost
Porta: 54320  ⬅️ PORTA DIFERENTE!
Banco: ri_ifba_v1
Usuário: postgres
Senha: 201099
```

## 🛠️ Conexões

### Dentro dos Containers Laravel

Os containers Laravel usam a **porta interna** (5432):

```env
DB_HOST=postgres
DB_PORT=5432  ⬅️ Porta interna do Docker
```

**Não precisa mudar nada no código!**

### Do Windows (DBeaver, pgAdmin, etc.)

Para acessar o banco do Docker do Windows:

```
Host: localhost (ou 127.0.0.1)
Porta: 54320  ⬅️ Porta externa
Banco: ri_ifba_v1
Usuário: postgres
Senha: 201099
```

### Via Adminer (Interface Web)

```bash
sudo make adminer-up
```

Acesse: http://localhost:8080

```
Sistema: PostgreSQL
Servidor: postgres
Usuário: postgres
Senha: 201099
Base: ri_ifba_v1
```

## 🆚 Comparação: Interno vs Externo

| Contexto | Host | Porta | Quando usar |
|----------|------|-------|-------------|
| **Containers Laravel** | `postgres` | `5432` | Conexões de dentro do Docker |
| **Windows (você)** | `localhost` | `54320` | DBeaver, pgAdmin, psql local |
| **Adminer** | `postgres` | `5432` | Interface web (dentro do Docker) |

## 🧪 Testando

### 1. Verificar se o container subiu

```bash
sudo docker compose ps
```

**Deve mostrar:**
```
NAME                IMAGE                    STATUS          PORTS
ri-ifba-postgres    postgres:16.11-alpine    Up (healthy)    0.0.0.0:54320->5432/tcp
```

### 2. Testar conexão do Windows

```bash
# Se tiver psql instalado no Windows
psql -h localhost -p 54320 -U postgres -d ri_ifba_v1

# Ou via WSL/Docker
sudo docker compose exec postgres psql -U postgres -d ri_ifba_v1
```

### 3. Verificar tabelas

```sql
\dt
```

Deve listar todas as tabelas do Laravel.

## ❌ Erros Comuns

### Erro: "Connection refused" na porta 54320

**Causa:** Container PostgreSQL não subiu ou não está healthy

**Solução:**
```bash
# Ver logs
sudo docker compose logs postgres

# Reiniciar
sudo docker compose restart postgres

# Aguardar ficar healthy
sudo docker compose ps
```

### Erro: Laravel não conecta ao banco

**Causa:** Variável `DB_PORT` errada no `.env`

**Solução:**
```bash
# Verificar .env
cat .env | grep DB_

# Deve ser:
DB_HOST=postgres
DB_PORT=5432  ⬅️ INTERNA (não mudar!)

# Se mudou para 54320, voltar para 5432
# A porta 54320 é só para acesso externo!
```

### Erro: Ainda diz "address already in use"

**Causa:** Containers antigos ainda rodando

**Solução:**
```bash
# Parar TUDO
sudo docker compose down -v

# Verificar se parou
sudo docker ps -a

# Remover se existir
sudo docker rm -f ri-ifba-postgres

# Recriar
sudo make setup
```

## 🔄 Alternativa: Parar PostgreSQL Local

Se preferir, pode parar o PostgreSQL local do Windows e usar a porta 5432 no Docker:

### Windows (PowerShell como Admin):

```powershell
# Ver serviços do PostgreSQL
Get-Service -Name *postgres*

# Parar serviço
Stop-Service -Name postgresql-x64-14  # Ajustar nome

# Desabilitar início automático
Set-Service -Name postgresql-x64-14 -StartupType Disabled
```

### Depois ajustar `.env.docker`:

```dotenv
DB_PORT_EXTERNAL=5432  # Volta para porta padrão
```

E recriar containers:

```bash
sudo docker compose down -v
sudo make setup
```

## 📊 Fluxo de Portas

### Configuração Atual (Recomendada):

```
Windows                Docker Network           Container
────────              ───────────────          ──────────

PostgreSQL Local
    ⬇️
  :5432 ────────────────────────────────────────❌ (não acessa)


localhost:54320 ───▶  (bridge)  ───▶  postgres:5432
                                          ⬇️
                                  PostgreSQL Container
                                      (Projeto)
```

### Conexões do Laravel:

```
ri-ifba-app ───▶ postgres:5432 ───▶ PostgreSQL Container
   (dentro          (nome do           (mesmo network)
    do Docker)       container)
```

**Tudo funciona automaticamente!** ✅

## 🎯 Resumo

✅ **Configurado:**
- PostgreSQL Docker usa porta externa **54320**
- Evita conflito com PostgreSQL local (5432)
- Laravel continua usando porta interna 5432
- Você pode usar os dois bancos simultaneamente

❌ **Não funciona:**
- Conectar de fora do Docker na porta 5432
- Mudar `DB_PORT` no `.env` para 54320

✅ **Funciona:**
- Laravel ➜ `postgres:5432` (interno)
- DBeaver ➜ `localhost:54320` (externo)
- Ambos bancos rodando ao mesmo tempo

---

## 🚀 Comandos Rápidos

```bash
# Limpar e recriar tudo
cd "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend"
sudo docker compose down -v
sudo make setup

# Ver logs do PostgreSQL
sudo docker compose logs -f postgres

# Acessar banco via CLI
sudo docker compose exec postgres psql -U postgres -d ri_ifba_v1

# Verificar saúde dos containers
sudo docker compose ps

# Testar API
curl http://localhost:8000/api/v1/cardapio/hoje
```

---

**Última atualização:** 13/01/2026  
**Versão:** 1.0.0  
**Status:** Problema resolvido - Porta 54320 configurada

