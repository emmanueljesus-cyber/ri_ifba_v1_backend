# RF15 – Importar Lista de Bolsistas

## ✅ Implementação Completa e Correta

### Objetivo
Permitir que o administrador importe uma lista de bolsistas via arquivo Excel/CSV, facilitando o cadastro e atualização em lote.

---

## 📋 Formato do Arquivo Excel/CSV

### Cabeçalhos Obrigatórios (primeira linha)
```
matricula | nome | email | turno | curso
```

### Exemplo de Dados
```csv
20231001,João Silva Santos,joao.silva@example.com,matutino,Técnico em Informática
20231002,Maria Oliveira Costa,maria.oliveira@example.com,vespertino,Técnico em Edificações
20231003,Pedro Santos Lima,pedro.santos@example.com,noturno,Técnico em Mecânica
```

### Campos

| Campo | Obrigatório | Descrição |
|-------|-------------|-----------|
| matricula | ✅ Sim | Matrícula do bolsista (única) |
| nome | ✅ Sim | Nome completo |
| email | ✅ Sim | Email válido (único) |
| turno | ✅ Sim | matutino, vespertino ou noturno |
| curso | ❌ Não | Nome do curso |

---

## 🚀 Endpoints

### 1. Download do Template
```http
GET /api/v1/admin/bolsistas/template
Authorization: Bearer {token}
```

**Resposta:** Arquivo `template_bolsistas.xlsx` com 3 exemplos

---

### 2. Importar Bolsistas
```http
POST /api/v1/admin/bolsistas/importar
Authorization: Bearer {token}
Content-Type: multipart/form-data

Body:
  file: arquivo.xlsx
```

**Resposta de Sucesso (201 ou 207):**
```json
{
  "data": {
    "total_importados": 5,
    "total_atualizados": 3,
    "criados": [
      {
        "matricula": "20231001",
        "nome": "João Silva Santos",
        "action": "created"
      }
    ],
    "atualizados": [
      {
        "matricula": "20231002",
        "nome": "Maria Oliveira Costa",
        "action": "updated"
      }
    ]
  },
  "errors": [],
  "meta": {
    "message": "Importação concluída",
    "total_processados": 8,
    "total_erros": 0
  }
}
```

**Resposta com Erros (207):**
```json
{
  "data": {
    "total_importados": 3,
    "total_atualizados": 2,
    "criados": [...],
    "atualizados": [...]
  },
  "errors": [
    {
      "linha": 5,
      "erro": "Email inválido: invalido.com"
    },
    {
      "linha": 7,
      "erro": "Turno inválido: tarde. Use: matutino, vespertino ou noturno"
    }
  ],
  "meta": {
    "message": "Importação concluída",
    "total_processados": 5,
    "total_erros": 2
  }
}
```

---

## 🔄 Comportamento

### Novo Usuário (matrícula não existe)
- ✅ Cria novo usuário
- ✅ Senha padrão = matrícula
- ✅ perfil = estudante
- ✅ bolsista = true
- ✅ limite_faltas_mes = 3

### Usuário Existente (matrícula existe)
- ✅ Atualiza: nome, email, turno, curso
- ✅ Define bolsista = true
- ✅ Define perfil = estudante
- ✅ Mantém senha existente

---

## ✅ Validações

### Arquivo
- Tipo: xlsx, xls, csv
- Tamanho máximo: 5MB
- Arquivo obrigatório

### Dados (por linha)
- ✅ Matrícula obrigatória
- ✅ Nome obrigatório
- ✅ Email obrigatório e válido
- ✅ Turno obrigatório e válido (matutino, vespertino, noturno)
- ✅ Aceita variações: manhã, tarde, noite

### Tratamento de Erros
- Linhas com erro são **puladas**
- Importação **continua** para as próximas linhas
- Retorna lista completa de erros ao final

---

## 🎯 Normalização de Turnos

O sistema aceita várias formas de escrever o turno:

| Excel | Salvo no Banco |
|-------|----------------|
| matutino | matutino |
| manhã | matutino |
| manha | matutino |
| vespertino | vespertino |
| tarde | vespertino |
| noturno | noturno |
| noite | noturno |

---

## 📦 Arquivos Criados

```
✅ app/Http/Requests/Admin/BolsistaImportRequest.php
✅ app/Services/BolsistaImportService.php
✅ app/Exports/BolsistaTemplateExport.php
✅ app/Http/Controllers/api/v1/Admin/BolsistaController.php (modificado)
✅ routes/api.php (modificado)
```

**Nenhuma migration foi necessária!** A tabela `users` já possui todos os campos.

---

## 🔒 Segurança

- ✅ Autenticação obrigatória (`auth:sanctum`)
- ✅ Apenas admins podem importar
- ✅ Validação de tipo de arquivo
- ✅ Limite de tamanho
- ✅ Validação de email único
- ✅ Validação de matrícula única

---

## 🧪 Como Testar

### 1. Download do Template
```bash
GET http://localhost:8000/api/v1/admin/bolsistas/template
Headers:
  Authorization: Bearer {seu_token}
```

### 2. Preencher Excel
- Baixar o arquivo `template_bolsistas.xlsx`
- Preencher com dados reais
- Salvar

### 3. Importar
```bash
POST http://localhost:8000/api/v1/admin/bolsistas/importar
Headers:
  Authorization: Bearer {seu_token}
Body (form-data):
  file: selecionar arquivo.xlsx
```

### 4. Verificar Resultado
- Verificar resposta JSON
- Checar `total_importados` e `total_atualizados`
- Se houver erros, verificar array `errors[]`

---

## ⚠️ Observações Importantes

1. **Senha Padrão**: Novos usuários recebem senha = matrícula
   - Devem alterá-la no primeiro acesso

2. **Dias da Semana**: A importação **NÃO** cadastra os dias da semana
   - Use o RF14 para configurar os dias após importar

3. **Turno**: Cada bolsista pode ter seu próprio turno
   - Diferente do cardápio, não há turno único para todos

4. **Email Único**: Emails duplicados causam erro
   - Linha com email duplicado é pulada

5. **Atualização em Lote**: Ideal para:
   - Atualizar dados no início do semestre
   - Correção de dados em massa
   - Migração de sistemas antigos

---

## 📊 Exemplo de Teste

### Template Excel:
| matricula | nome | email | turno | curso |
|-----------|------|-------|-------|-------|
| 20231001 | João Silva | joao@example.com | matutino | Técnico em Informática |
| 20231002 | Maria Costa | maria@example.com | vespertino | Técnico em Edificações |
| 20231003 | Pedro Lima | pedro@example.com | noturno | Técnico em Mecânica |

### Resultado Esperado:
- 3 usuários criados ou atualizados
- Todos com `bolsista = true`
- Cada um com seu turno específico
- Senha = matrícula para novos usuários

---

## ✅ Status

**IMPLEMENTADO E FUNCIONAL**

- Request criado ✅
- Service criado ✅
- Export criado ✅
- Controller atualizado ✅
- Rotas registradas ✅
- Validações implementadas ✅
- Tratamento de erros ✅
- Sem migrations necessárias ✅
- Documentação criada ✅

**Pronto para uso! 🎉**

