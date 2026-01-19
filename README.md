# 🍽️ RI IFBA Backend

Sistema de Gestão de Refeições do Restaurante Institucional do IFBA.

**Stack:** Laravel 12 • PHP 8.4 • PostgreSQL 16 • Docker

---

## 🚀 Início Rápido

### Pré-requisitos
- Docker Desktop com WSL 2 habilitado
- Git

### 1. Clone e Configure

```bash
# No WSL/Ubuntu
cd ~
git clone <url-do-repositorio> ri_ifba_v1
cd ri_ifba_v1/ri_ifba_v1_backend

# Copie o arquivo de ambiente
cp .env.example .env
```

### 2. Suba os Containers

```bash
# Limpar e reconstruir (primeira vez ou após problemas)
docker compose down -v
docker compose build
docker compose up -d
```

### 3. Configure a Aplicação

```bash
# Instalar dependências PHP
docker compose exec app composer install --no-interaction --prefer-dist

# Gerar chave da aplicação
docker compose exec app php artisan key:generate

# Rodar migrações e popular banco
docker compose exec app php artisan migrate --seed
```

### 4. Verifique se Está Funcionando

```bash
# Ver status dos containers
docker compose ps

# Testar API
curl http://localhost:8000/api/v1/cardapio/hoje
```

---

## 🔗 Acessos

- **API:** http://localhost:8000/api/v1
- **Documentação de Rotas:** Veja `routes/api.php`

### Credenciais de Teste (seeds)

| Tipo | Matrícula | Senha |
|------|-----------|-------|
| Admin | `10000000001` | `password` |
| Bolsista | `20231160001` | `password` |
| Não-Bolsista | `20232160001` | `password` |

---

## 📝 Comandos Úteis

### Gerenciar Containers

```bash
# Ver logs em tempo real
docker compose logs -f

# Ver logs apenas do app
docker compose logs -f app

# Parar containers
docker compose down

# Resetar tudo (⚠️ apaga dados do banco)
docker compose down -v
docker compose up -d
```

### Executar Comandos Laravel

```bash
# Entrar no container
docker compose exec app bash

# Rodar migrações
docker compose exec app php artisan migrate

# Popular banco de dados
docker compose exec app php artisan db:seed

# Resetar banco (migrate fresh + seed)
docker compose exec app php artisan migrate:fresh --seed

# Limpar caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# Ver rotas
docker compose exec app php artisan route:list
```

### Testes

```bash
# Rodar todos os testes
docker compose exec app php artisan test

# Rodar testes específicos
docker compose exec app php artisan test --filter=AuthTest
```

---

## 🐛 Troubleshooting

### Container não sobe / vendor/autoload.php não encontrado

```bash
# Reconstruir do zero
docker compose down -v
docker compose build --no-cache
docker compose up -d

# Reinstalar dependências
docker compose exec app composer install --no-interaction --prefer-dist
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### Composer timeout

```bash
# Aumentar timeout manualmente
docker compose exec app sh -c "COMPOSER_PROCESS_TIMEOUT=900 composer install --no-interaction --prefer-dist"
```

### Erro de permissão no Git

```bash
# Dentro do container
docker compose exec app git config --global --add safe.directory /var/www/html
```

### PostgreSQL não conecta

```bash
# Verificar se está rodando
docker compose ps

# Reiniciar postgres
docker compose restart postgres

# Ver logs do postgres
docker compose logs postgres
```

### Porta 8000 já está em uso

```bash
# Encontrar processo usando a porta
# Windows PowerShell:
Get-Process -Id (Get-NetTCPConnection -LocalPort 8000).OwningProcess

# Linux/WSL:
lsof -i :8000

# Ou mudar a porta no docker-compose.yml
```

---

## 📂 Estrutura Principal

```
ri_ifba_v1_backend/
├── docker-compose.yml      # Configuração Docker
├── Dockerfile              # Imagem do app
├── .env                    # Variáveis de ambiente
├── app/
│   ├── Http/Controllers/   # Controllers da API
│   ├── Models/             # Models Eloquent
│   ├── Services/           # Lógica de negócio
│   └── Enums/              # Enums (Status, Tipos, etc)
├── routes/
│   └── api.php            # Rotas da API
├── database/
│   ├── migrations/        # Migrações do banco
│   └── seeders/           # Seeds (dados iniciais)
├── config/                # Configurações
└── tests/                 # Testes automatizados
```

---

## 🔧 Desenvolvimento no PHPStorm

1. Abra o projeto no PHPStorm
2. Vá em **Services** → **Docker** ou **Docker Compose**
3. Selecione o arquivo `docker-compose.yml`
4. Clique com botão direito → **Up**
5. Use o terminal WSL integrado para rodar comandos

---

## 📚 Documentação Adicional

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

## 📄 Licença

MIT
