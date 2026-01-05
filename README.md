# 🍽️ Sistema de Restaurante Institucional - IFBA

Sistema web para gerenciamento de refeições do Restaurante Institucional do IFBA, desenvolvido com Laravel 11.

## 📋 Sobre o Projeto

Sistema completo para controle de:
- ✅ Cardápios diários
- ✅ Confirmação de presenças (QR Code + Manual)
- ✅ Gestão de bolsistas
- ✅ Relatórios de validação
- ✅ Marcação de faltas justificadas/injustificadas

---

## 🚀 Tecnologias

- **Backend:** Laravel 11 (PHP 8.2+)
- **Banco de Dados:** PostgreSQL / SQLite
- **Frontend:** HTML5, CSS3, JavaScript
- **Autenticação:** Laravel Sanctum
- **QR Code:** jsQR (scanner via câmera)

---

## 📦 Instalação

### **Requisitos:**
- PHP 8.2 ou superior
- Composer
- PostgreSQL ou SQLite
- Node.js (opcional, para assets)

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
DB_CONNECTION=sqlite  # ou pgsql
DB_DATABASE=database/database.sqlite

# 5. Execute as migrations
php artisan migrate

# 6. (Opcional) Popule o banco com dados de teste
php artisan db:seed

# 7. Inicie o servidor
php artisan serve
```

Acesse: `http://localhost:8000`

---

## 🎯 Funcionalidades Implementadas

### **RF13 - Validação de Presença** ✅

#### **1️⃣ QR Code Scanner**
- Validação via câmera do celular/tablet
- Token SHA-256 seguro
- Interface: `http://localhost:8000/validar-presenca-qrcode.html`

#### **2️⃣ Busca por Matrícula**
- Validação manual (fallback)
- Busca rápida por nome ou matrícula

#### **3️⃣ Lista do Dia**
- Mostra apenas alunos cadastrados para aquele dia da semana
- Checkbox direto para marcar presença
- Marcação de faltas individual
- Interface: `http://localhost:8000/lista-presencas-dia.html`

#### **4️⃣ Relatório de Validações**
- Auditoria completa (quem validou e quando)
- Estatísticas por admin
- Timeline de validações
- Interface: `http://localhost:8000/relatorio-validacoes.html`

---

## 📊 Estrutura do Projeto

```
ri_ifba_v1_backend/
├── app/
│   ├── Http/Controllers/api/v1/Admin/
│   │   ├── CardapioController.php
│   │   ├── PresencaController.php
│   │   └── RelatorioValidacaoController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Cardapio.php
│   │   ├── Refeicao.php
│   │   └── Presenca.php
│   └── Enums/
│       ├── StatusPresenca.php
│       └── TurnoRefeicao.php
├── public/
│   ├── validar-presenca-qrcode.html
│   ├── lista-presencas-dia.html
│   └── relatorio-validacoes.html
├── routes/
│   └── api.php
├── database/
│   ├── migrations/
│   └── seeders/
├── docs/
│   ├── RF13_VALIDACAO_QRCODE_MATRICULA.md
│   ├── RF13_LISTA_PRESENCAS_DIA.md
│   ├── RELATORIO_VALIDACOES_ADMIN.md
│   └── SISTEMA_PRESENCA_COMPLETO.md
└── README.md
```

---

## 🔌 Endpoints Principais

### **Cardápios**
```http
GET    /api/v1/admin/cardapios
POST   /api/v1/admin/cardapios
PUT    /api/v1/admin/cardapios/{id}
DELETE /api/v1/admin/cardapios/{id}
```

### **Presenças**
```http
GET  /api/v1/admin/presencas
POST /api/v1/admin/presencas/confirmar
POST /api/v1/admin/presencas/validar-qrcode
POST /api/v1/admin/presencas/{id}/marcar-falta
```

### **Relatórios**
```http
GET /api/v1/admin/relatorios/validacoes
GET /api/v1/admin/relatorios/validacoes/por-admin
GET /api/v1/admin/relatorios/validacoes/timeline
```

---

## 🧪 Testes

Para testar as funcionalidades:

### **1. Lista de Presenças:**
```
http://localhost:8000/lista-presencas-dia.html
```

### **2. Validação por QR Code:**
```
http://localhost:8000/validar-presenca-qrcode.html
```

### **3. Relatório de Validações:**
```
http://localhost:8000/relatorio-validacoes.html
```

---

## 📖 Documentação

A documentação completa está na pasta `docs/`:

- **RF13_VALIDACAO_QRCODE_MATRICULA.md** - Sistema de QR Code
- **RF13_LISTA_PRESENCAS_DIA.md** - Lista de presenças
- **RELATORIO_VALIDACOES_ADMIN.md** - Relatórios
- **SISTEMA_PRESENCA_COMPLETO.md** - Visão geral do sistema

---

## 🔒 Segurança

- ✅ Token SHA-256 para QR Code
- ✅ Autenticação via Sanctum
- ✅ Validação de permissões (Admin)
- ✅ Proteção contra SQL Injection (Eloquent ORM)
- ✅ CORS configurado
- ✅ Arquivos sensíveis no .gitignore

---

## 🎯 Estados de Presença

| Status | Descrição |
|--------|-----------|
| `null` | Sem registro (não confirmou) |
| `confirmado` | Aluno confirmou que vai comer |
| `validado` | Admin validou presença |
| `falta_justificada` | Ausente com justificativa |
| `falta_injustificada` | Ausente sem justificativa |

---

## 🔄 Fluxo do Sistema

```
1. Aluno confirma presença (via app/web)
   ↓
2. Sistema gera QR Code único
   ↓
3. Admin valida presença:
   - Escaneia QR Code (rápido)
   - OU busca por matrícula (manual)
   - OU marca na lista do dia
   ↓
4. Presença registrada com auditoria
   (quem validou e quando)
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

