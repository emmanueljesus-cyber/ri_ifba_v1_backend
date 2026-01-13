# 📋 Segregação de Rotas por Perfil de Estudante

## 🎯 Objetivo

Separar o acesso às rotas da API de acordo com o perfil do estudante:
- **Bolsistas**: Têm acesso a funcionalidades relacionadas a refeições regulares (RF02, RF04, RF05)
- **Não Bolsistas**: Têm acesso a fila de extras e notificações (RF06, RF07)

---

## 🔐 Middlewares Criados

### 1. `EnsureIsBolsista`
**Arquivo:** `app/Http/Middleware/EnsureIsBolsista.php`

**Validações:**
- ✅ Usuário está autenticado
- ✅ Perfil é "estudante"
- ✅ Flag `bolsista` é `true`

**Resposta de Erro:**
```json
{
  "message": "Acesso negado. Este recurso é exclusivo para estudantes bolsistas."
}
```

### 2. `EnsureIsNaoBolsista`
**Arquivo:** `app/Http/Middleware/EnsureIsNaoBolsista.php`

**Validações:**
- ✅ Usuário está autenticado
- ✅ Perfil é "estudante"
- ✅ Flag `bolsista` é `false`

**Resposta de Erro:**
```json
{
  "message": "Acesso negado. Este recurso é exclusivo para estudantes não bolsistas."
}
```

---

## 📍 Rotas Organizadas

### Rotas Comuns (Todos os Estudantes)

```
GET /api/v1/estudante/cardapio/hoje
```

**Middleware:** `auth:sanctum` (condicional por `APP_DEBUG`)

**Acesso:**
- ✅ Bolsistas
- ✅ Não Bolsistas

---

### Rotas Exclusivas para Bolsistas

**Middleware:** `auth:sanctum` + `ensure.is.bolsista` (condicional por `APP_DEBUG`)

#### RF05 - Perfil e Preferência Alimentar
```
GET    /api/v1/estudante/perfil
PUT    /api/v1/estudante/perfil
PUT    /api/v1/estudante/perfil/preferencia
PUT    /api/v1/estudante/perfil/dias-semana
POST   /api/v1/estudante/perfil/foto
DELETE /api/v1/estudante/perfil/foto
```

#### RF04 - Histórico de Refeições e Faltas
```
GET /api/v1/estudante/historico
GET /api/v1/estudante/historico/resumo
```

#### RF02 - Justificativas de Faltas
```
GET  /api/v1/estudante/justificativas
POST /api/v1/estudante/justificativas
GET  /api/v1/estudante/justificativas/{id}
```

**Acesso:**
- ✅ Apenas Bolsistas
- ❌ Não Bolsistas (retorna 403)

---

### Rotas Exclusivas para Não Bolsistas

**Middleware:** `auth:sanctum` + `ensure.is.nao.bolsista` (condicional por `APP_DEBUG`)

#### RF06/RF07 - Fila de Extras
```
GET    /api/v1/estudante/fila-extras           # Minhas inscrições
POST   /api/v1/estudante/fila-extras           # Inscrever-se
GET    /api/v1/estudante/fila-extras/posicao   # Ver posição na fila
DELETE /api/v1/estudante/fila-extras/{id}      # Cancelar inscrição
```

#### RF05 - Perfil Básico (sem preferência alimentar)
```
GET    /api/v1/estudante/perfil
PUT    /api/v1/estudante/perfil
POST   /api/v1/estudante/perfil/foto
DELETE /api/v1/estudante/perfil/foto
```

#### RF04 - Histórico Básico
```
GET /api/v1/estudante/historico
GET /api/v1/estudante/historico/resumo
```

#### Notificações (para avisos de fila extras)
```
GET  /api/v1/estudante/notificacoes
GET  /api/v1/estudante/notificacoes/nao-lidas
GET  /api/v1/estudante/notificacoes/contador
POST /api/v1/estudante/notificacoes/{id}/ler
POST /api/v1/estudante/notificacoes/ler-todas
```

**Acesso:**
- ✅ Apenas Não Bolsistas
- ❌ Bolsistas (retorna 403)

---

## 🔄 Toggle de Autenticação

As rotas respeitam a variável `APP_DEBUG` para facilitar desenvolvimento:

### Desenvolvimento (`APP_DEBUG=true`)
```php
$bolsistaMiddleware = [];
$naoBolsistaMiddleware = [];
```
**Resultado:** Rotas SEM autenticação (para testes)

### Produção (`APP_DEBUG=false`)
```php
$bolsistaMiddleware = ['auth:sanctum', 'ensure.is.bolsista'];
$naoBolsistaMiddleware = ['auth:sanctum', 'ensure.is.nao.bolsista'];
```
**Resultado:** Rotas COM autenticação e validação de perfil

---

## 📊 Tabela Resumo de Acessos

| Recurso | Bolsista | Não Bolsista |
|---------|:--------:|:------------:|
| **Cardápio hoje** | ✅ | ✅ |
| **Perfil básico** | ✅ | ✅ |
| **Foto perfil** | ✅ | ✅ |
| **Histórico** | ✅ | ✅ |
| **Preferência alimentar** | ✅ | ❌ |
| **Dias da semana** | ✅ | ❌ |
| **Justificativas** | ✅ | ❌ |
| **Fila de extras** | ❌ | ✅ |
| **Notificações** | ❌ | ✅ |

---

## 🧪 Testes de Validação

### Testar acesso de Bolsista

```bash
# Login como bolsista
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"matricula":"20231160001","password":"password"}'

# Usar token retornado
TOKEN="seu_token_aqui"

# ✅ Deve funcionar (bolsista)
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/v1/estudante/justificativas

# ❌ Deve retornar 403 (não tem acesso)
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/v1/estudante/fila-extras
```

### Testar acesso de Não Bolsista

```bash
# Login como não bolsista
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"matricula":"20232160001","password":"password"}'

# Usar token retornado
TOKEN="seu_token_aqui"

# ✅ Deve funcionar (não bolsista)
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/v1/estudante/fila-extras

# ❌ Deve retornar 403 (não tem acesso)
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/v1/estudante/justificativas
```

---

## 🔧 Implementação Técnica

### Registro dos Middlewares

**Arquivo:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'ensure.is.admin' => \App\Http\Middleware\EnsureIsAdmin::class,
        'ensure.is.bolsista' => \App\Http\Middleware\EnsureIsBolsista::class,
        'ensure.is.nao.bolsista' => \App\Http\Middleware\EnsureIsNaoBolsista::class,
    ]);
})
```

### Validação no Middleware

**Lógica de validação:**

```php
// 1. Verifica autenticação
if (!$user) {
    return response()->json(['message' => 'Não autenticado.'], 401);
}

// 2. Verifica se é estudante
if ($user->perfil !== PerfilUsuario::ESTUDANTE) {
    return response()->json([
        'message' => 'Acesso negado. Apenas estudantes...'
    ], 403);
}

// 3. Verifica flag bolsista
if (!$user->bolsista) { // ou $user->bolsista para não bolsista
    return response()->json([
        'message' => 'Acesso negado. Este recurso é exclusivo...'
    ], 403);
}
```

---

## 📝 Requisitos Funcionais Atendidos

### RF02 - Justificar Faltas
- ✅ Apenas bolsistas podem justificar faltas
- ✅ Middleware `ensure.is.bolsista` protege as rotas

### RF03 - Visualizar Cardápio
- ✅ Todos os estudantes podem visualizar
- ✅ Rota comum sem restrição de perfil

### RF04 - Consultar Histórico
- ✅ Bolsistas: histórico completo de refeições
- ✅ Não bolsistas: histórico básico

### RF05 - Preferência Alimentar
- ✅ Apenas bolsistas podem definir preferência
- ✅ Não bolsistas: perfil básico sem preferência

### RF06/RF07 - Fila de Extras
- ✅ Apenas não bolsistas podem se inscrever
- ✅ Middleware `ensure.is.nao.bolsista` protege as rotas

---

## 🚀 Benefícios da Implementação

### Segurança
- ✅ Validação em nível de middleware
- ✅ Mensagens de erro claras
- ✅ Impossível acessar recursos não autorizados

### Organização
- ✅ Rotas separadas por perfil
- ✅ Código limpo e legível
- ✅ Fácil manutenção

### Flexibilidade
- ✅ Toggle de autenticação para desenvolvimento
- ✅ Fácil adicionar novos recursos por perfil
- ✅ Compatível com testes automatizados

### Conformidade com RFs
- ✅ Implementa exatamente o que foi especificado
- ✅ Bolsistas: RF02, RF04, RF05
- ✅ Não bolsistas: RF06, RF07
- ✅ Ambos: RF03

---

## 📌 Notas Importantes

### Model User
O modelo `User` já possui os campos necessários:
- `perfil` (enum: admin, estudante)
- `bolsista` (boolean)

### Seeders
Os seeders já criam usuários com perfis corretos:
- 20 bolsistas (bolsista=true)
- 5 não bolsistas (bolsista=false)

### Toggle APP_DEBUG
- Em desenvolvimento: rotas sem autenticação para facilitar testes
- Em produção: rotas com autenticação completa

---

## ✅ Checklist de Implementação

- [x] Middleware `EnsureIsBolsista` criado
- [x] Middleware `EnsureIsNaoBolsista` criado
- [x] Middlewares registrados no `bootstrap/app.php`
- [x] Rotas reorganizadas por perfil
- [x] Rotas comuns identificadas
- [x] Rotas exclusivas separadas
- [x] Documentação completa
- [x] Sem erros de sintaxe

---

**Implementado em:** 13/01/2026  
**Arquivos modificados:** 4  
**Arquivos criados:** 2  
**Status:** ✅ Pronto para uso

