# 🔧 Configuração das Credenciais do Banco - Docker

## 📋 Suas Credenciais

```
DB_CONNECTION=pgsql
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

---

## 🐳 Para usar com Docker

### 1. Copiar arquivo de ambiente Docker

```bash
# Windows (PowerShell)
Copy-Item .env.docker .env

# Ou via WSL/Linux
cp .env.docker .env
```

### 2. O arquivo `.env` para Docker deve ter:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres          # ⚠️ Nome do container (não 127.0.0.1)
DB_PORT=5432
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

### 3. Iniciar Docker

```bash
make setup
# ou
docker-compose up -d
```

---

## 💻 Para usar SEM Docker (WSL direto)

### O arquivo `.env` atual está correto:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1         # ✅ Localhost (PostgreSQL rodando no WSL)
DB_PORT=5432
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

### Comandos:

```bash
wsl php artisan migrate
wsl php artisan db:seed
wsl php artisan serve
```

---

## 🔄 Alternando entre Docker e WSL

### Opção 1: Manter 2 arquivos

```bash
.env              # Para usar WSL direto (DB_HOST=127.0.0.1)
.env.docker       # Para usar Docker (DB_HOST=postgres)
```

**Trocar:**
```bash
# Usar Docker
cp .env.docker .env
make up

# Usar WSL
cp .env.example .env
# Editar DB_HOST=127.0.0.1 e DB_PASSWORD=201099
wsl php artisan serve
```

### Opção 2: Usar variáveis do docker-compose (Recomendado)

O `docker-compose.yml` já está configurado para pegar do `.env`:

```yaml
environment:
  - DB_HOST=postgres
  - DB_PORT=5432
  - DB_DATABASE=${DB_DATABASE:-ri_ifba_v1}
  - DB_USERNAME=${DB_USERNAME:-postgres}
  - DB_PASSWORD=${DB_PASSWORD:-201099}
```

**Não precisa mudar nada!** O Docker Compose sobrescreve as variáveis automaticamente.

---

## ✅ Resumo

### Docker (DB dentro do container)
```env
DB_HOST=postgres  # Nome do container
```

### WSL/Local (DB no host)
```env
DB_HOST=127.0.0.1  # Localhost
```

### ⚡ Solução Rápida

Mantenha seu `.env` atual como está (com `DB_HOST=127.0.0.1`) e use Docker:

```bash
docker-compose up -d
```

O Docker Compose vai **sobrescrever automaticamente** o `DB_HOST` para `postgres` dentro dos containers! 🎉

---

## 🧪 Testar Conexão

### Com Docker:
```bash
docker-compose up -d
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Sem Docker (WSL):
```bash
wsl php artisan tinker
>>> DB::connection()->getPdo();
```

---

**✅ Suas credenciais já estão configuradas corretamente!**

