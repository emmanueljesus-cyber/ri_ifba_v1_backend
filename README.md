# 🍽️ Sistema de Restaurante Institucional - IFBA

Sistema web para gerenciamento de refeições do Restaurante Institucional do IFBA, desenvolvido com Laravel 12.

## 📋 Sobre o Projeto

Sistema completo para controle de:
- ✅ Cardápios diários
- ✅ Confirmação de presenças (QR Code + Manual)
- ✅ Gestão de bolsistas
- ✅ **Gerenciamento de usuários (CRUD completo)** ⭐ NOVO
- ✅ Relatórios de validação
- ✅ Marcação de faltas justificadas/injustificadas
- ✅ Importação de cardápios via Excel

---

## 🚀 Tecnologias

- **Backend:** Laravel 12 (PHP 8.2+)
- **Banco de Dados:** PostgreSQL (SQLite para testes)
- **Autenticação:** Laravel Sanctum
- **Importação Excel:** Maatwebsite/Excel

---

## 📦 Instalação

### **Requisitos:**
- PHP 8.2 ou superior
- Composer
- PostgreSQL

### **Passos:**

```bash
# 1. Clone o repositório
git clone https://github.com/SEU_USUARIO/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# 2. Instale as dependências
composer install

# 3. Configure o ambiente
cp .env.example .env
php artisan key:generate

# 4. Configure o banco de dados no .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ri_ifba
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# 5. Execute as migrations
php artisan migrate

# 6. (Opcional) Popule o banco com dados de teste
php artisan db:seed

# 7. Inicie o servidor
php artisan serve
```

Acesse: `http://localhost:8000`

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
