# 🐳 Como Funciona o Docker + Banco de Dados no Projeto

## ❓ Sua Dúvida Respondida

### 1. **O banco de dados está DENTRO do Docker?**

✅ **SIM!** O PostgreSQL roda dentro de um container Docker separado.

**Estrutura:**
```
┌─────────────────────────────────────────────┐
│           DOCKER COMPOSE                    │
├─────────────────────────────────────────────┤
│                                             │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐│
│  │  NGINX   │──▶│   APP    │──▶│ POSTGRES ││
│  │ :80      │   │ (Laravel)│   │ :5432    ││
│  └──────────┘   └──────────┘   └──────────┘│
│                       │                     │
│                       ▼                     │
│                 ┌──────────┐                │
│                 │  REDIS   │                │
│                 │ :6379    │                │
│                 └──────────┘                │
└─────────────────────────────────────────────┘
```

**Você NÃO precisa:**
- ❌ Instalar PostgreSQL no seu computador
- ❌ Instalar Redis no seu computador
- ❌ Configurar manualmente o banco

**O Docker faz tudo automaticamente!**

---

## 🔧 Como o Banco de Dados é Criado

### Passo a Passo Automático:

#### 1. Ao executar `make setup` ou `docker compose up`:

```yaml
# docker-compose.yml define o container PostgreSQL:
postgres:
  image: postgres:16.11-alpine        # Baixa imagem oficial
  environment:
    POSTGRES_DB: ri_ifba_v1           # Cria banco automaticamente
    POSTGRES_USER: postgres
    POSTGRES_PASSWORD: 201099
  volumes:
    - postgres-data:/var/lib/postgresql/data  # Persiste dados
```

#### 2. O entrypoint.sh aguarda o banco ficar pronto:

```bash
# docker/entrypoint.sh (executado automaticamente)
echo "⏳ Aguardando PostgreSQL..."
until pg_isready -h postgres -p 5432 -U postgres
do
  echo "Aguardando PostgreSQL ficar pronto..."
  sleep 2
done
echo "✅ PostgreSQL está pronto!"
```

#### 3. Depois executa as migrations:

```bash
echo "📊 Executando migrations..."
php artisan migrate --force
```

#### 4. E popula com dados de teste:

```bash
php artisan db:seed
```

**TUDO AUTOMÁTICO!** 🎉

---

## ❌ Por Que Não Funciona Após Baixar?

### Problema 1: Falta o arquivo `.env`

**Causa:** O arquivo `.env` não é versionado no Git (por segurança)

**Solução:**
```bash
# Copiar o template
cp .env.docker .env

# Ou o make setup já faz isso
make setup
```

### Problema 2: Docker não está rodando

**Causa:** Docker Desktop não está aberto ou serviço parado

**Solução:**
```bash
# Windows: Abrir Docker Desktop

# WSL/Linux: Iniciar serviço
sudo service docker start

# Verificar se está rodando
docker ps
```

### Problema 3: Permissão do Docker (WSL/Linux)

**Causa:** Usuário não tem permissão para usar Docker

**Solução:**
```bash
sudo usermod -aG docker $USER
newgrp docker
```

### Problema 4: Volumes antigos/conflitantes

**Causa:** Dados antigos de execuções anteriores

**Solução:**
```bash
# Limpar tudo
docker compose down -v
docker system prune -af

# Recomeçar
make setup
```

### Problema 5: Portas em uso

**Causa:** Outra aplicação usando portas 8000, 5432, 6379

**Solução:**
```bash
# Ver quem está usando
netstat -ano | findstr :8000  # Windows
lsof -i :8000                 # Linux

# Mudar porta no .env
APP_PORT=8001
```

---

## ✅ Fluxo Correto Após Clonar

### Primeiro Clone (Setup Inicial):

```bash
# 1. Clonar repositório
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# 2. Garantir que Docker está rodando
docker ps  # Deve listar containers ou estar vazio

# 3. Setup completo (faz TUDO)
make setup
```

**O que `make setup` faz:**
1. ✅ Copia `.env.docker` para `.env`
2. ✅ Faz build dos containers (baixa imagens)
3. ✅ Cria container PostgreSQL vazio
4. ✅ Cria container Redis
5. ✅ Sobe todos os containers
6. ✅ Aguarda PostgreSQL ficar pronto
7. ✅ Instala dependências do Composer
8. ✅ **Gera APP_KEY automaticamente**
9. ✅ **Executa migrations (cria tabelas)**
10. ✅ **Popula banco com dados de teste**

**Tempo:** 3-5 minutos

---

## 🗄️ Sobre o Banco de Dados

### O banco é criado automaticamente em:

```
Container: ri-ifba-postgres
Imagem: postgres:16.11-alpine
Host: postgres (nome do container)
Porta: 5432
Banco: ri_ifba_v1
Usuário: postgres
Senha: 201099
```

### Onde ficam os dados?

```bash
# Volume Docker persistente
docker volume ls

# Nome do volume
ri-ifba-postgres-data
```

**Importante:** Os dados persistem mesmo se você parar os containers!

### Como acessar o banco?

#### Opção 1: Adminer (Interface Web)
```bash
make adminer-up
# Acesse: http://localhost:8080

# Credenciais:
# Sistema: PostgreSQL
# Servidor: postgres
# Usuário: postgres
# Senha: 201099
# Base: ri_ifba_v1
```

#### Opção 2: CLI do PostgreSQL
```bash
# Via make
make db-shell

# Ou diretamente
docker compose exec postgres psql -U postgres -d ri_ifba_v1
```

#### Opção 3: Cliente externo (DBeaver, pgAdmin)
```
Host: localhost  # ou 127.0.0.1
Porta: 5432
Banco: ri_ifba_v1
Usuário: postgres
Senha: 201099
```

---

## 🔄 Cenários Comuns

### Cenário 1: Primeira Vez

```bash
git clone <repo>
cd ri_ifba_v1_backend
make setup
# ✅ Tudo criado do zero!
```

### Cenário 2: Já Rodou Antes (mesma máquina)

```bash
cd ri_ifba_v1_backend
make up
# ✅ Usa banco existente (dados preservados)
```

### Cenário 3: Máquina Nova (mesmo projeto)

```bash
git clone <repo>
cd ri_ifba_v1_backend
make setup
# ✅ Cria banco novo (dados zerados)
```

### Cenário 4: Resetar Banco

```bash
cd ri_ifba_v1_backend
make migrate-fresh
# ✅ Reseta tabelas e popula de novo
```

### Cenário 5: Limpar Tudo

```bash
cd ri_ifba_v1_backend
make clean
# ⚠️ Remove volumes (perde dados!)
make setup
# ✅ Recria tudo do zero
```

---

## 🆚 Docker vs Instalação Manual

### Com Docker (Recomendado):

**Vantagens:**
- ✅ Não precisa instalar PostgreSQL
- ✅ Não precisa instalar Redis
- ✅ Ambiente isolado
- ✅ Setup em 1 comando
- ✅ Funciona igual em qualquer máquina
- ✅ Banco criado automaticamente

**Desvantagens:**
- ⚠️ Precisa ter Docker instalado
- ⚠️ Usa mais recursos (RAM/CPU)

### Sem Docker (Manual):

**Vantagens:**
- ✅ Usa menos recursos
- ✅ Acesso direto ao sistema

**Desvantagens:**
- ❌ Precisa instalar PostgreSQL manualmente
- ❌ Precisa configurar banco manualmente
- ❌ Pode ter problemas de versão
- ❌ Diferente em cada máquina
- ❌ Setup mais demorado

---

## 🐛 Troubleshooting do Banco

### Problema: "address already in use" (Porta 5432 ocupada)

**Erro completo:**
```
failed to bind host port 0.0.0.0:5432/tcp: address already in use
```

**Causa:** Você tem PostgreSQL instalado localmente no Windows usando a porta 5432

**Solução Aplicada:** O projeto foi configurado para usar a porta **54320** externamente:

```bash
# 1. Limpar containers antigos
sudo docker compose down -v

# 2. Iniciar novamente (já configurado para porta 54320)
sudo make setup
```

**Como acessar agora:**
- **Do Laravel (interno):** `postgres:5432` (não muda nada!)
- **Do Windows (DBeaver/pgAdmin):** `localhost:54320`

📖 **Ver documentação completa:** [ERRO_PORTA_5432.md](./ERRO_PORTA_5432.md)

### Problema: "Connection refused" ou "could not connect"

**Causa:** PostgreSQL não está rodando ou não ficou pronto

**Solução:**
```bash
# Ver logs do PostgreSQL
docker compose logs postgres

# Verificar health
docker compose ps

# Se não estiver healthy, reiniciar
docker compose restart postgres

# Aguardar ficar pronto
sleep 10
```

### Problema: "password authentication failed"

**Causa:** Senha incorreta no .env

**Solução:**
```bash
# Verificar .env
cat .env | grep DB_

# Deve ser:
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099

# Se diferente, corrigir e reiniciar
docker compose restart app
```

### Problema: "database does not exist"

**Causa:** Banco não foi criado

**Solução:**
```bash
# O container PostgreSQL cria automaticamente
# Mas se não criou, recriar:
docker compose down -v
docker compose up -d postgres
sleep 10

# Verificar se criou
docker compose exec postgres psql -U postgres -l
# Deve listar ri_ifba_v1
```

### Problema: Tabelas não existem

**Causa:** Migrations não foram executadas

**Solução:**
```bash
# Executar migrations
docker compose exec app php artisan migrate

# Ou resetar tudo
docker compose exec app php artisan migrate:fresh --seed
```

---

## 📊 Resumo Visual

### O que acontece quando você roda `make setup`:

```
1. make setup
   ↓
2. Copia .env.docker → .env
   ↓
3. docker compose build
   ├─ Baixa imagem PHP 8.4
   ├─ Baixa imagem PostgreSQL 16.11
   ├─ Baixa imagem Redis 7
   └─ Baixa imagem Nginx 1.25
   ↓
4. docker compose up -d
   ├─ Cria container postgres (vazio)
   ├─ Cria container redis
   ├─ Cria container app
   └─ Cria container nginx
   ↓
5. Aguarda postgres ficar pronto
   ↓
6. composer install
   ↓
7. php artisan key:generate (automático)
   ↓
8. php artisan migrate (cria tabelas)
   ↓
9. php artisan db:seed (27 usuários)
   ↓
10. ✅ PRONTO!
    Backend: http://localhost:8000
    Adminer: http://localhost:8080
```

---

## ✅ Checklist Pós-Clone

Depois de clonar o projeto, verifique:

- [ ] Docker Desktop está rodando (ou serviço docker)
- [ ] Executou `make setup` (ou comandos manuais)
- [ ] Arquivo `.env` existe (copiado do `.env.docker`)
- [ ] Containers estão rodando: `docker compose ps`
- [ ] PostgreSQL está healthy
- [ ] Banco `ri_ifba_v1` foi criado
- [ ] Tabelas foram criadas (migrations)
- [ ] Dados de teste foram inseridos (seed)
- [ ] API responde: `curl http://localhost:8000/api/v1/cardapio/hoje`

---

## 🎯 Resposta Direta às Suas Dúvidas

### 1. "O banco de dados está no Docker?"

✅ **SIM!** PostgreSQL roda em um container separado.

O `docker-compose.yml` define:
- Container `postgres` com imagem PostgreSQL 16.11
- Cria banco `ri_ifba_v1` automaticamente
- Senha padrão: `201099`

### 2. "Por que não funciona depois de baixar?"

Provavelmente porque:
1. ❌ Não executou `make setup` (ou `docker compose up`)
2. ❌ Arquivo `.env` não existe (não foi copiado)
3. ❌ Docker não está rodando
4. ❌ Permissão do Docker (WSL)

**Solução:**
```bash
cd ri_ifba_v1_backend
make setup
# Aguardar 3-5 minutos
# Pronto!
```

---

## 📞 Se Ainda Não Funcionar

Execute e me mostre o resultado:

```bash
# 1. Verificar Docker
docker --version
docker compose version
docker ps

# 2. Verificar .env
ls -la .env
cat .env | grep DB_

# 3. Tentar setup
make setup

# 4. Ver logs
docker compose logs
```

Com esses comandos, consigo identificar exatamente o problema! 🎯

---

**Última atualização:** 13/01/2026  
**Versão:** 1.0.0  
**Status:** Documentação completa sobre Docker + Banco de Dados

