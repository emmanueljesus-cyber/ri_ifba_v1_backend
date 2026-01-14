# 🍽️ RI IFBA Backend

Sistema de Gestão de Refeições do Restaurante Institucional do IFBA.

**Stack:** Laravel 12 • PHP 8.4 • PostgreSQL 16 • Redis 7 • Docker

---

## 🚀 Início Rápido

### Pré-requisitos
- [Docker Desktop](https://www.docker.com/products/docker-desktop) instalado e rodando
- No Windows: WSL 2 ativado

### Instalação

**Windows (PowerShell):**
```powershell
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
.\setup.ps1
```

**Linux/macOS ou WSL:**
```bash
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
./setup.sh
```

**Pronto!** 🎉

---

## 🔗 Acessos

| Serviço | URL |
|---------|-----|
| API | http://localhost:8000 |
| Adminer (banco) | http://localhost:8080 |

### Credenciais de Teste

| Perfil | Matrícula | Senha |
|--------|-----------|-------|
| Admin | `10000000001` | `password` |
| Bolsista | `20231160001` | `password` |

---

## 📝 Comandos Úteis

```bash
# Ver status dos containers
docker compose ps

# Ver logs
docker compose logs -f

# Acessar shell do container
docker compose exec app bash

# Executar migrations
docker compose exec app php artisan migrate

# Parar tudo
docker compose down

# Recomeçar do zero
docker compose down -v && ./setup.sh
```

---

## 📚 Documentação

| Arquivo | Descrição |
|---------|-----------|
| [DEPLOY_GUIDE.md](docs/DEPLOY_GUIDE.md) | Como fazer deploy em produção |
| [requests/api.http](requests/api.http) | Exemplos de requisições da API |

> 📂 **Documentação completa de desenvolvimento:** Veja a pasta [`docs/`](docs/) com explicações detalhadas sobre seeders, regras de negócio, configurações e troubleshooting.

---

## 🏗️ Estrutura do Projeto

```
app/
├── Http/Controllers/api/v1/
│   ├── Admin/           # Rotas administrativas
│   ├── Estudante/       # Rotas dos estudantes
│   └── Publico/         # Rotas públicas
├── Models/              # Modelos do banco
├── Services/            # Lógica de negócio
└── Enums/               # Enumerações

routes/
└── api.php              # Definição das rotas

database/
├── migrations/          # Estrutura do banco
└── seeders/             # Dados de teste
```

---

## 🔐 Perfis de Acesso

| Perfil | Permissões |
|--------|------------|
| **Admin** | Acesso total: cardápios, presenças, justificativas, usuários, relatórios |
| **Bolsista** | Consultar cardápios, ver histórico próprio, enviar justificativas |
| **Não Bolsista** | Apenas consultar cardápios públicos |

---

## 🧪 Testes

```bash
docker compose exec app php artisan test
```

---

## 📄 Licença

MIT
