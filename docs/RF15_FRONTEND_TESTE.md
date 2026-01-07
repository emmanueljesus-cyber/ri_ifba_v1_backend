# 🎨 Frontend de Teste - RF15 Importar Bolsistas

## 📋 Visão Geral

Interface web visual criada em Blade para testar a funcionalidade de importação de bolsistas.

---

## 🚀 Como Acessar

### URL de Acesso
```
http://localhost:8000/bolsistas/import
```

### Arquivos Criados
```
✅ resources/views/bolsistas/import.blade.php
✅ routes/web.php (modificado)
```

---

## ⚙️ Configuração

### 1. Configurar Token de Autenticação

Abra o arquivo `resources/views/bolsistas/import.blade.php` e localize a linha:

```javascript
const TOKEN = 'YOUR_TOKEN_HERE'; // Linha 453
```

**Substitua por um dos métodos:**

#### Opção A: Token Fixo (para testes)
```javascript
const TOKEN = 'seu_token_aqui';
```

#### Opção B: Variável do Backend
```javascript
const TOKEN = '{{ $token ?? "" }}';
```

E passe o token na rota:
```php
Route::get('/bolsistas/import', function () {
    $token = 'seu_token_aqui'; // ou buscar do auth
    return view('bolsistas.import', compact('token'));
})->name('bolsistas.import');
```

#### Opção C: Session Storage (Recomendado)
```javascript
const TOKEN = localStorage.getItem('auth_token') || 'YOUR_TOKEN_HERE';
```

---

## 🎯 Funcionalidades Implementadas

### 1. Download do Template
- ✅ Botão para baixar template Excel
- ✅ Mostra formato esperado
- ✅ Exemplos visuais

### 2. Upload de Arquivo
- ✅ Drag and Drop
- ✅ Clique para selecionar
- ✅ Validação de tipo (.xlsx, .xls, .csv)
- ✅ Validação de tamanho (máx 5MB)
- ✅ Informações do arquivo selecionado

### 3. Importação
- ✅ Loading animado
- ✅ Tratamento de erros
- ✅ Feedback visual

### 4. Resultados
- ✅ Cards com estatísticas
- ✅ Lista de bolsistas criados
- ✅ Lista de bolsistas atualizados
- ✅ Lista de erros encontrados
- ✅ Botão para nova importação

---

## 🎨 Interface

### Layout
```
┌─────────────────────────────────────┐
│       Header (Gradiente Roxo)       │
├─────────────────────────────────────┤
│                                     │
│  📥 Passo 1: Download Template      │
│  [Botão Download] [Exemplo Formato] │
│                                     │
│  📤 Passo 2: Upload Arquivo         │
│  [Área Drag & Drop]                 │
│  [Botão Importar]                   │
│                                     │
│  📊 Resultados                       │
│  [Stats] [Listas] [Botão Reset]     │
│                                     │
└─────────────────────────────────────┘
```

### Cores
- **Primária:** #667eea (Roxo)
- **Secundária:** #764ba2 (Roxo escuro)
- **Sucesso:** #28a745 (Verde)
- **Erro:** #dc3545 (Vermelho)
- **Info:** #17a2b8 (Azul)

---

## 🧪 Como Testar

### Passo a Passo

1. **Iniciar Servidor**
   ```bash
   php artisan serve
   ```

2. **Acessar Interface**
   ```
   http://localhost:8000/bolsistas/import
   ```

3. **Configurar Token**
   - Editar arquivo `import.blade.php`
   - Substituir `YOUR_TOKEN_HERE` por token válido
   - Ou implementar sistema de login

4. **Download Template**
   - Clicar em "Baixar Template Excel"
   - Salvar arquivo

5. **Preencher Excel**
   - Abrir template_bolsistas.xlsx
   - Preencher dados dos bolsistas
   - Salvar

6. **Importar**
   - Arrastar arquivo para área de upload
   - OU clicar e selecionar arquivo
   - Clicar em "Importar Bolsistas"

7. **Verificar Resultados**
   - Ver estatísticas (criados, atualizados, erros)
   - Ver listas detalhadas
   - Ver erros se houver

8. **Nova Importação**
   - Clicar em "Nova Importação"
   - Repetir processo

---

## 📱 Recursos da Interface

### Alertas
```javascript
showAlert(message, type);
// type: 'success', 'error', 'warning'
```

### Drag & Drop
- Arraste arquivo para área azul
- Feedback visual ao arrastar
- Validação automática

### Animações
- Slide down para alertas
- Spinner de loading
- Smooth scroll

### Responsivo
- Adapta a diferentes tamanhos de tela
- Grid flexível para stats
- Mobile friendly

---

## 🔧 Customização

### Alterar Cores
```css
/* No <style> do blade */
.header {
    background: linear-gradient(135deg, #SUA_COR 0%, #SUA_COR2 100%);
}
```

### Alterar API Base
```javascript
const API_BASE = '/api/v1/admin/bolsistas';
// Mudar se API estiver em outro servidor
```

### Alterar Tamanho Máximo
```javascript
// Linha ~429
if (file.size > 5 * 1024 * 1024) { // 5MB
    // Alterar para 10MB:
    // if (file.size > 10 * 1024 * 1024) {
}
```

---

## 🐛 Troubleshooting

### Problema: Token inválido
**Solução:**
1. Verificar se token está configurado
2. Verificar se token não expirou
3. Gerar novo token via API

### Problema: CORS Error
**Solução:**
```php
// config/cors.php
'paths' => ['api/*', 'bolsistas/*'],
'supports_credentials' => true,
```

### Problema: Arquivo não faz upload
**Solução:**
1. Verificar tamanho (máx 5MB)
2. Verificar tipo (.xlsx, .xls, .csv)
3. Verificar permissões do servidor

### Problema: Template não baixa
**Solução:**
1. Verificar se rota `/api/v1/admin/bolsistas/template` existe
2. Verificar token de autenticação
3. Verificar se pacote `maatwebsite/excel` está instalado

---

## 📊 Estrutura de Dados

### Request
```javascript
FormData {
    file: File (arquivo.xlsx)
}
```

### Response (Sucesso)
```json
{
    "data": {
        "total_importados": 5,
        "total_atualizados": 3,
        "criados": [
            {
                "matricula": "20231001",
                "nome": "João Silva",
                "action": "created"
            }
        ],
        "atualizados": [
            {
                "matricula": "20231002",
                "nome": "Maria Costa",
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

### Response (Com Erros)
```json
{
    "data": { ... },
    "errors": [
        {
            "linha": 5,
            "erro": "Email inválido: abc"
        }
    ],
    "meta": { ... }
}
```

---

## ✨ Melhorias Futuras

### Possíveis Adições
- [ ] Sistema de login integrado
- [ ] Preview do arquivo antes de importar
- [ ] Validação em tempo real
- [ ] Histórico de importações
- [ ] Download de relatório de erros
- [ ] Suporte para múltiplos arquivos
- [ ] Barra de progresso
- [ ] Notificações push

---

## 📝 Notas Importantes

1. **Token:** Configure o token antes de usar
2. **CSRF:** Token CSRF já está incluído
3. **Validação:** Cliente valida tipo e tamanho
4. **Servidor:** Validação final é no backend
5. **Feedback:** Interface mostra todos os detalhes

---

## 🎯 Checklist de Implementação

- [x] View Blade criada
- [x] Rota web configurada
- [x] CSS responsivo
- [x] JavaScript funcional
- [x] Drag & Drop
- [x] Validações
- [x] Alertas
- [x] Loading
- [x] Resultados detalhados
- [x] Documentação

---

## 📞 Suporte

### Arquivos
- **View:** `resources/views/bolsistas/import.blade.php`
- **Rota:** `routes/web.php`
- **Docs:** Este arquivo

### Acesso
```
URL: http://localhost:8000/bolsistas/import
Route Name: bolsistas.import
```

---

## ✅ Status

**✅ FRONTEND IMPLEMENTADO E FUNCIONAL**

A interface está pronta para uso imediato após configurar o token de autenticação!

---

**Criado em:** 07/01/2026  
**Tecnologias:** Blade, CSS, JavaScript Vanilla  
**Status:** ✅ Pronto para uso

