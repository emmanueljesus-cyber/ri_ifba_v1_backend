# 🎨 Como Usar o Frontend de Teste

## 🚀 Início Rápido

### 1. Iniciar o Servidor
```bash
php artisan serve
```

### 2. Acessar a Interface
```
http://localhost:8000/bolsistas/import
```

### 3. Configurar Token (IMPORTANTE!)

Abra o arquivo:
```
resources/views/bolsistas/import.blade.php
```

Na **linha 453**, localize:
```javascript
const TOKEN = 'YOUR_TOKEN_HERE';
```

**Substitua por um token válido de admin.**

### Como Obter um Token?

#### Opção 1: Via Postman
```http
POST http://localhost:8000/api/v1/login
Body: {
    "matricula": "admin_matricula",
    "password": "senha_admin"
}
```

Copie o `token` da resposta.

#### Opção 2: Temporariamente Desabilitar Auth
No arquivo `routes/api.php`, a rota já está configurada para funcionar com `APP_DEBUG=true`.

---

## 📋 Passo a Passo de Uso

### Passo 1: Download do Template
1. Na interface, clique em **"📥 Baixar Template Excel"**
2. Salve o arquivo `template_bolsistas.xlsx`

### Passo 2: Preencher Excel
1. Abra o arquivo no Excel
2. Preencha as linhas com dados dos bolsistas
3. Formato:
   ```
   matricula | nome | email | turno | curso
   ```
4. Salve o arquivo

### Passo 3: Importar
1. Arraste o arquivo para a **área azul** (Drag & Drop)
   - **OU** clique na área para selecionar
2. Clique em **"🚀 Importar Bolsistas"**
3. Aguarde o processamento

### Passo 4: Ver Resultados
- **Cards de estatísticas** (criados, atualizados, erros)
- **Lista de bolsistas criados** (verde)
- **Lista de bolsistas atualizados** (azul)
- **Lista de erros** (vermelho) se houver

### Passo 5: Nova Importação
- Clique em **"🔄 Nova Importação"**
- Repita o processo

---

## ✅ Exemplo de Excel

| matricula | nome | email | turno | curso |
|-----------|------|-------|-------|-------|
| 20231001 | João Silva | joao@email.com | matutino | Técnico em Informática |
| 20231002 | Maria Costa | maria@email.com | vespertino | Técnico em Edificações |
| 20231003 | Pedro Lima | pedro@email.com | noturno | Técnico em Mecânica |

**Turnos aceitos:**
- matutino, manhã, manha
- vespertino, tarde
- noturno, noite

---

## 🎯 Funcionalidades

### ✅ Drag & Drop
- Arraste arquivo direto para área de upload
- Feedback visual

### ✅ Validações
- Tipo de arquivo (.xlsx, .xls, .csv)
- Tamanho máximo (5MB)
- Formato do email
- Turnos válidos

### ✅ Feedback Visual
- Alertas coloridos
- Loading animado
- Estatísticas em cards
- Listas detalhadas

---

## 🐛 Problemas Comuns

### ❌ "Token inválido"
**Solução:** Configure o token na linha 453 do arquivo blade

### ❌ "CORS Error"
**Solução:** Verifique se `APP_DEBUG=true` no `.env`

### ❌ "Arquivo não é aceito"
**Solução:** Use apenas .xlsx, .xls ou .csv

### ❌ "Template não baixa"
**Solução:**
1. Verifique se token está configurado
2. Verifique se rota API existe
3. Teste a rota API no Postman primeiro

---

## 📱 Interface

### Layout Visual
```
┌────────────────────────────────────┐
│   📚 RF15 - Importar Bolsistas     │  (Header Roxo)
├────────────────────────────────────┤
│                                    │
│  📥 Passo 1: Download Template     │
│  [Botão] [Exemplo]                 │
│                                    │
│  📤 Passo 2: Upload                │
│  [Drag & Drop Area]                │
│  [Botão Importar]                  │
│                                    │
│  📊 Resultados                      │
│  [5 Criados] [3 Atualizados]       │
│  [0 Erros]                         │
│                                    │
│  [Listas Detalhadas]               │
│  [Botão Reset]                     │
│                                    │
└────────────────────────────────────┘
```

### Cores
- 🟣 Roxo: Primária
- 🟢 Verde: Sucesso/Criado
- 🔵 Azul: Info/Atualizado
- 🔴 Vermelho: Erro
- ⚠️ Amarelo: Aviso

---

## 🔧 Configuração Avançada

### Mudar URL da API
```javascript
// Linha 452
const API_BASE = '/api/v1/admin/bolsistas';
// Mudar para API externa:
// const API_BASE = 'http://api.exemplo.com/api/v1/admin/bolsistas';
```

### Aumentar Tamanho Máximo
```javascript
// Linha 429
if (file.size > 5 * 1024 * 1024) { // 5MB
// Mudar para 10MB:
// if (file.size > 10 * 1024 * 1024) {
```

---

## 📊 Resultado Esperado

### Sucesso Total
```
✅ 5 Criados
✅ 3 Atualizados
✅ 0 Erros
✅ 8 Total Processados
```

### Com Erros
```
✅ 3 Criados
✅ 2 Atualizados
❌ 2 Erros
ℹ️ 5 Total Processados

Erros:
- Linha 5: Email inválido
- Linha 7: Turno inválido
```

---

## 📝 Checklist

- [ ] Servidor rodando (`php artisan serve`)
- [ ] Token configurado (linha 453)
- [ ] Arquivo Excel preparado
- [ ] Dados preenchidos corretamente
- [ ] Interface acessível (localhost:8000/bolsistas/import)

---

## ✅ Pronto!

A interface está **100% funcional** após configurar o token!

**Acesse:** http://localhost:8000/bolsistas/import

---

**Criado em:** 07/01/2026  
**Status:** ✅ Funcionando  
**Tecnologia:** Blade + JavaScript Vanilla

