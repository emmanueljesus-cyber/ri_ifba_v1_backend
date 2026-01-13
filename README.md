# 🍽️ Sistema de Restaurante Institucional - IFBA

Sistema web para gerenciamento de refeições do Restaurante Institucional do IFBA, desenvolvido com Laravel 12.

## 🚀 Começando Rápido

> **📖 GUIA COMPLETO:** Ver arquivo **[`COMO_RODAR.md`](COMO_RODAR.md)** - Passo a passo detalhado de como clonar e rodar o projeto!

### Com Docker (Recomendado):
```bash
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# Apenas WSL/Linux: resolver permissão do Docker
sudo usermod -aG docker $USER && newgrp docker

# Executar setup
make setup
```
**Pronto!** Acesse http://localhost:8000 🎉

**Login:** Admin: `10000000001` / Senha: `password`

---

### Sem Docker (WSL):
```bash
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
wsl composer install
cp .env.example .env
wsl php artisan key:generate
# Configure .env com suas credenciais do banco
wsl php artisan migrate --seed
wsl php artisan serve
```

---

## 📋 Sobre o Projeto

Sistema web completo para gerenciamento do Restaurante Institucional do IFBA, com **controle de acesso baseado em perfis** e módulos funcionais especializados.

### 👥 Perfis de Acesso

O sistema possui **3 perfis de usuários** com permissões distintas:

| Perfil | Descrição | Acesso |
|--------|-----------|--------|
| **👨‍💼 Admin** | Gestor do sistema | Acesso completo a todos os módulos |
| **👨‍🎓 Bolsista** | Estudante com bolsa alimentação | Acesso limitado (consulta e justificativas próprias) |
| **👤 Não Bolsista** | Estudante sem bolsa | Acesso público (apenas consulta de cardápios) |

### 📦 Módulos do Sistema

#### 🍽️ **Módulo de Cardápios**
Gerenciamento completo dos cardápios do restaurante.

**Funcionalidades:**
- ✅ Cadastro, edição e exclusão de cardápios
- ✅ Importação em massa via Excel (XLSX, XLS, CSV)
- ✅ Consulta de cardápio por período (dia, semana, mês)
- ✅ Organização por turno (almoço/jantar)

**Acesso por perfil:**
- **Admin:** CRUD completo + importação Excel
- **Bolsista:** Apenas consulta
- **Não Bolsista:** Apenas consulta

---

#### ✅ **Módulo de Presenças**
Controle e validação de presenças dos bolsistas nas refeições.

**Funcionalidades:**
- ✅ Confirmação de presença manual (admin)
- ✅ Validação via QR Code (em desenvolvimento)
- ✅ Visualização de lista de bolsistas do dia
- ✅ Busca por matrícula
- ✅ Histórico de presenças

**Acesso por perfil:**
- **Admin:** Validar presenças de todos os bolsistas
- **Bolsista:** Ver apenas histórico próprio
- **Não Bolsista:** Sem acesso

---

#### 📝 **Módulo de Justificativas**
Gerenciamento de faltas justificadas e injustificadas.

**Funcionalidades:**
- ✅ Envio de justificativa de falta (antes ou depois)
- ✅ Análise e aprovação/reprovação de justificativas
- ✅ Marcação de faltas injustificadas
- ✅ Anexo de documentos comprobatórios
- ✅ Notificações por email

**Acesso por perfil:**
- **Admin:** Analisar e decidir sobre todas as justificativas
- **Bolsista:** Enviar e acompanhar justificativas próprias
- **Não Bolsista:** Sem acesso

---

#### 👥 **Módulo de Gestão de Usuários**
Administração completa de usuários do sistema.

**Funcionalidades:**
- ✅ CRUD completo de usuários
- ✅ Gerenciamento de perfis (admin, estudante)
- ✅ Controle de bolsistas (ativar/desligar)
- ✅ Definição de limite de faltas por mês
- ✅ Histórico de alterações

**Acesso por perfil:**
- **Admin:** Acesso completo
- **Bolsista:** Sem acesso
- **Não Bolsista:** Sem acesso

---

#### 📊 **Módulo de Relatórios**
Geração de relatórios gerenciais e estatísticos.

**Funcionalidades:**
- ✅ Relatório de validações de presença
- ✅ Relatório de validações por admin
- ✅ Timeline de validações
- ✅ Estatísticas de faltas e presenças
- ✅ Exportação em Excel/PDF

**Acesso por perfil:**
- **Admin:** Acesso completo a todos os relatórios
- **Bolsista:** Sem acesso
- **Não Bolsista:** Sem acesso

---

### 🔐 Resumo de Permissões por Módulo

| Módulo | Admin | Bolsista | Não Bolsista |
|--------|:-----:|:--------:|:------------:|
| **Cardápios** | ✅ CRUD + Import | 👁️ Consulta | 👁️ Consulta |
| **Presenças** | ✅ Validar todas | 👁️ Ver próprias | ❌ |
| **Justificativas** | ✅ Analisar todas | ✅ Enviar próprias | ❌ |
| **Usuários** | ✅ CRUD completo | ❌ | ❌ |
| **Relatórios** | ✅ Todos | ❌ | ❌ |

**Legenda:** ✅ Acesso completo | 👁️ Apenas leitura | ❌ Sem acesso

---

## 🚀 Tecnologias

- **Backend:** Laravel 12.44.0 (PHP 8.4.16)
- **Banco de Dados:** PostgreSQL 16.11
- **Autenticação:** Laravel Sanctum 4.2.1
- **Importação Excel:** Maatwebsite/Excel 3.1.67
- **Cache & Queue:** Redis 7
- **Servidor Web:** Nginx 1.25
- **Containerização:** Docker + Docker Compose
- **Testes:** PHPUnit 11.5.46
- **Qualidade:** PHPStan 2.1.33 + Larastan 3.8.1

**📊 Versões Completas:** Ver `VERSOES_DEPENDENCIAS.md`  
**🐳 Docker Setup:** Ver `DOCKER_GUIDE.md` e `DEPLOY_GUIDE.md`

---

## 📦 Instalação

### ⚠️ Pré-requisitos

#### Para usar Docker (Opção 1):
- **Docker Desktop** instalado e rodando
  - Windows: [Download Docker Desktop](https://www.docker.com/products/docker-desktop)
  - Ativar integração WSL 2 nas configurações
  - **📖 Guia completo:** `INSTALACAO_DOCKER_WSL.md`

#### Para instalação manual (Opção 2):
- **PHP:** 8.2+ (Testado: 8.4.16)
- **Composer:** 2.0+ (Testado: 2.9.3)
- **PostgreSQL:** 12+ (Testado: 16.11)
- **WSL2:** Ubuntu 24.04 LTS (Windows)

---

### 🐳 Opção 1: Docker (Recomendado)

**A forma mais rápida e fácil!** Ambiente completo isolado com PostgreSQL, Redis e Nginx.

#### Setup Rápido (1 comando):

```bash
# 1. Clone o repositório
git clone https://github.com/SEU_USUARIO/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# 2. Setup completo automático
make setup
```

**✨ O que acontece automaticamente:**
- ✅ Copia `.env.docker` para `.env`
- ✅ Sobe todos os containers (Nginx, Laravel, PostgreSQL, Redis)
- ✅ **Gera `APP_KEY` automaticamente**
- ✅ Instala dependências do Composer
- ✅ Executa migrations
- ✅ Popula banco com dados de teste

**Pronto! 🎉** Acesse:
- 🌐 Backend API: http://localhost:8000
- 🗄️ Adminer (Gerenciador BD): http://localhost:8080

#### Configuração do Banco de Dados:

O Docker usa as seguintes credenciais (já configuradas no `.env.docker`):

```env
DB_CONNECTION=pgsql
DB_HOST=postgres          # Nome do container
DB_PORT=5432
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

**💡 Dica:** O `APP_KEY` é gerado automaticamente pelo `entrypoint.sh` quando o container sobe!

#### Comandos Úteis:

```bash
# Gerenciamento de containers
make up          # Iniciar containers
make down        # Parar containers
make restart     # Reiniciar containers
make logs        # Ver logs em tempo real
make ps          # Status dos containers

# Desenvolvimento
make shell       # Acessar shell da aplicação
make tinker      # Laravel Tinker
make test        # Executar testes

# Banco de dados
make migrate     # Executar migrations
make seed        # Popular dados de teste
make db-shell    # Acessar PostgreSQL
make db-backup   # Fazer backup do banco

# Qualidade de código
make pint        # Formatar código
make phpstan     # Análise estática

# Informações
make info        # Ver versões instaladas
make help        # Ver TODOS os comandos (50+)
```

#### Credenciais de Acesso (Usuários de Teste):

| Perfil | Matrícula | Senha | Descrição |
|--------|-----------|-------|-----------|
| **Admin** | 10000000001 | password | Acesso completo |
| **Bolsistas** | 20231160001-020 | password | 20 usuários |
| **Não Bolsistas** | 20232160001-005 | password | 5 usuários |

**📋 Lista completa:** Ver `CREDENCIAIS_ACESSO.md`

#### Documentação Docker:

- **📖 Guia Completo:** `DOCKER_GUIDE.md` - Uso detalhado, troubleshooting
- **🚀 Deploy Produção:** `DEPLOY_GUIDE.md` - Passo a passo para deploy
- **🔑 APP_KEY Automático:** `APP_KEY_AUTOMATICO.md` - Como funciona
- **🗄️ Configuração BD:** `CONFIGURACAO_BANCO.md` - Credenciais e conexão

---

### 💻 Opção 2: Instalação Manual (WSL/Linux)

**Para quem prefere rodar sem Docker.**

#### Requisitos:
- **PHP:** 8.2+ (Testado: 8.4.16)
- **Composer:** 2.0+ (Testado: 2.9.3)
- **PostgreSQL:** 12+ (Testado: 16.11)
- **Node.js:** 18+ (Opcional - Testado: 20.19.6 LTS)
- **WSL2:** Ubuntu 24.04 LTS (recomendado para Windows)

#### Passos de Instalação:

**⚠️ No Windows, execute os comandos via WSL:**

```bash
# 1. Clone o repositório
git clone https://github.com/SEU_USUARIO/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# 2. Instale as dependências
wsl composer install

# 3. Configure o ambiente
cp .env.example .env
wsl php artisan key:generate

# 4. Configure o banco de dados no .env
nano .env
# Editar:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=sua_senha_aqui

# 5. Execute as migrations
wsl php artisan migrate

# 6. (Opcional) Popule o banco com dados de teste
wsl php artisan db:seed

# 7. Inicie o servidor
wsl php artisan serve
```

**Acesse:** http://localhost:8000

#### Credenciais de Teste:

Após executar `php artisan db:seed`, você terá:

- **Admin:** 10000000001 / password
- **Bolsistas:** 20231160001 até 20231160020 / password
- **Não Bolsistas:** 20232160001 até 20232160005 / password

**📋 Credenciais de Teste:** Ver `CREDENCIAIS_ACESSO.md`  
**📊 Versões & Dependências:** Ver `VERSOES_DEPENDENCIAS.md`

---

## 🔑 APP_KEY Automático (Docker)

Quando você usa Docker, o `APP_KEY` é **gerado automaticamente** pelo script `entrypoint.sh` ao subir o container!

**Você NÃO precisa executar `php artisan key:generate` manualmente!** ✅

### Como funciona:

1. Container inicia
2. Script `entrypoint.sh` detecta se `APP_KEY` está vazio
3. Gera automaticamente: `php artisan key:generate --force`
4. Aplicação pronta para uso!

**📚 Documentação:** Ver `APP_KEY_AUTOMATICO.md`

---

## 🗄️ Configuração do Banco de Dados

### Docker (Automático):
```env
DB_HOST=postgres          # Nome do container
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=201099
```

### Instalação Manual (WSL):
```env
DB_HOST=127.0.0.1         # Localhost
DB_DATABASE=ri_ifba_v1
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

**📚 Documentação:** Ver `CONFIGURACAO_BANCO.md`

---

## 🔐 Toggle de Autenticação (Desenvolvimento)

As rotas `/api/v1/admin/*` usam autenticação condicional:

| `APP_DEBUG` | Comportamento |
|-------------|---------------|
| `true`      | Rotas admin **SEM** autenticação (desenvolvimento/teste) |
| `false`     | Rotas admin **COM** `auth:sanctum` + `ensure.is.admin` (produção) |

Configure no `.env`:
```env
APP_DEBUG=true   # Desenvolvimento (sem auth)
APP_DEBUG=false  # Produção (com auth)
```

---

## 📤 Importação de Cardápios (Excel)

### Endpoint
```http
POST /api/v1/admin/cardapios/import
Content-Type: multipart/form-data
```

### Parâmetros
| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `file` | File | Sim | Arquivo Excel (xlsx, xls, csv) |
| `turno[]` | Array | Não | Turnos: `almoco`, `jantar` (padrão: `almoco`) |
| `debug` | Boolean | Não | Retorna dados de debug do arquivo |

### Limites
- **Tamanho máximo:** 5MB
- **Formatos:** `.xlsx`, `.xls`, `.csv`

### Resposta (padrão JSON)
```json
{
  "data": [
    { "id": 1, "data": "2026-01-06", "turno": "almoco", "action": "created" }
  ],
  "errors": [],
  "meta": {
    "total_criados": 1,
    "total_erros": 0
  }
}
```

---

## 🔌 Endpoints Principais

Todas as respostas seguem o padrão: `{ data, errors, meta }`

### **Cardápios**
```http
GET    /api/v1/admin/cardapios              # Listar (paginado)
POST   /api/v1/admin/cardapios              # Criar
POST   /api/v1/admin/cardapios/import       # Importar Excel
GET    /api/v1/admin/cardapios/{id}         # Detalhe
PUT    /api/v1/admin/cardapios/{id}         # Atualizar
DELETE /api/v1/admin/cardapios/{id}         # Deletar
DELETE /api/v1/admin/cardapios              # Deletar todos
POST   /api/v1/admin/cardapios/multiple     # Deletar múltiplos (ids[])
POST   /api/v1/admin/cardapios/date-range   # Deletar por período
```

### **Presenças**
```http
GET  /api/v1/admin/presencas
POST /api/v1/admin/presencas/confirmar
POST /api/v1/admin/presencas/{userId}/confirmar
POST /api/v1/admin/presencas/{id}/marcar-falta
POST /api/v1/admin/presencas/validar-qrcode
GET  /api/v1/admin/presencas/{id}/qrcode
```

### **Bolsistas**
```http
GET  /api/v1/admin/bolsistas
GET  /api/v1/admin/bolsistas/dia
POST /api/v1/admin/bolsistas/{userId}/confirmar-presenca
POST /api/v1/admin/bolsistas/{userId}/marcar-falta
```

### **Relatórios**
```http
GET /api/v1/admin/relatorios/validacoes
GET /api/v1/admin/relatorios/validacoes/por-admin
GET /api/v1/admin/relatorios/validacoes/timeline
```

### **Rotas Públicas (sem auth)**
```http
GET /api/v1/cardapio/hoje
GET /api/v1/cardapio/semanal
GET /api/v1/cardapio/mensal
```

---

## 📊 Estrutura do Projeto

```
ri_ifba_v1_backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/api/v1/Admin/
│   │   │   ├── CardapioController.php
│   │   │   ├── PresencaController.php
│   │   │   ├── BolsistaController.php
│   │   │   └── RelatorioValidacaoController.php
│   │   └── Requests/Admin/
│   │       ├── CardapioImportRequest.php
│   │       ├── CardapioStoreRequest.php
│   │       └── CardapioUpdateRequest.php
│   ├── Services/
│   │   ├── CardapioService.php
│   │   └── CardapioImportService.php
│   ├── Models/
│   └── Enums/
├── routes/
│   └── api.php
├── database/
│   ├── migrations/
│   └── seeders/
└── docs/
```

---

## 🧪 Testes

```bash
# Rodar todos os testes
php artisan test

# Testar API manualmente (com APP_DEBUG=true)
curl http://localhost:8000/api/v1/admin/cardapios
```

---

## 📖 Documentação

Documentação na pasta `docs/`. Arquivos legados em `docs/archive/`.

---

## 🔒 Segurança

- ✅ Autenticação via Sanctum (produção)
- ✅ Validação de permissões (Admin)
- ✅ Proteção SQL Injection (Eloquent)
- ✅ Validação de uploads (tipo/tamanho)
- ✅ CORS configurado

---

## 🎯 Estados de Presença

| Status | Descrição |
|--------|-----------|
| `null` | Sem registro (aluno ainda não foi marcado) |
| `confirmado` | Admin confirmou presença (aluno compareceu) |
| `falta_justificada` | Aluno justificou falta (antecipada ou posterior) |
| `falta_injustificada` | Aluno faltou sem justificativa |
| `cancelado` | Admin cancelou a refeição do dia |

---

## 🔄 Fluxo do Sistema

```
1. Admin visualiza lista de bolsistas do dia
   ↓
2. Admin marca presença do aluno:
   - Via botão "presente" na lista
   - OU via leitura de QR Code
   - OU via busca por matrícula
   ↓
3. Status atualizado para "confirmado"
   ↓
4. Se aluno faltou:
   - Aluno pode justificar → "falta_justificada"
   - Sem justificativa → "falta_injustificada"
   ↓
5. Se refeição cancelada → "cancelado"
```

---

## 📚 Documentação Disponível

Este projeto possui documentação completa e detalhada:

### 🐳 Docker
- **`DOCKER_GUIDE.md`** - Guia completo de uso do Docker (350+ linhas)
  - Arquitetura
  - Comandos
  - Troubleshooting
  - Monitoramento
  
- **`DEPLOY_GUIDE.md`** - Guia de deploy em produção (450+ linhas)
  - Checklist pré-deploy
  - Configuração SSL/HTTPS
  - Backup automático
  - Segurança

- **`DOCKER_SUMMARY.md`** - Sumário executivo
  - Visão geral
  - Benefícios
  - Próximos passos

### 🔑 Configuração
- **`APP_KEY_AUTOMATICO.md`** - Geração automática do APP_KEY
- **`CONFIGURACAO_BANCO.md`** - Configuração do banco de dados
- **`CREDENCIAIS_ACESSO.md`** - Lista de usuários e senhas de teste

### 📊 Versões & Dependências
- **`VERSOES_DEPENDENCIAS.md`** - Versões completas do stack
  - PHP, Laravel, PostgreSQL, Redis, etc
  - Lista de dependências (150+ pacotes)
  - Comandos úteis

### 📖 Documentação Técnica (pasta `docs/`)
- Análise de requisitos
- Diagramas UML
- Modelagem do banco
- Casos de uso
- Frontend guidelines

### 🚀 Começar Agora
1. **Para desenvolvimento:** Siga `DOCKER_GUIDE.md`
2. **Para produção:** Siga `DEPLOY_GUIDE.md`
3. **Para credenciais:** Veja `CREDENCIAIS_ACESSO.md`

---

## 🤝 Contribuindo

1. Faça um Fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

## 📝 Convenções de Commit

```
feat: Nova funcionalidade
fix: Correção de bug
docs: Atualização de documentação
refactor: Refatoração de código
test: Adição de testes
chore: Tarefas de manutenção
```

---

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

## 👥 Autores

**Equipe de Desenvolvimento - TCC IFBA**

---

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação na pasta `docs/` ou abra uma issue.

---

## 🎉 Agradecimentos

- Instituto Federal da Bahia (IFBA)
- Comunidade Laravel
- Biblioteca jsQR

---

**Desenvolvido com ❤️ para o IFBA**

---

## 📊 Status do Projeto

✅ **RF13 - Validação de Presença:** Implementado  
🚧 **RF14 - Dashboard Admin:** Em desenvolvimento  
📋 **RF15 - Relatórios Mensais:** Planejado  

---

**Última atualização:** Janeiro 2026
