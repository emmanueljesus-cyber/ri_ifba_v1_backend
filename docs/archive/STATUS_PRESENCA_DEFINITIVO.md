# 📋 Documentação Definitiva - Status de Presença

## 🎯 Status Disponíveis (FINAL)

```php
enum StatusPresenca: string
{
    case CONFIRMADO          = 'confirmado';
    case FALTA_JUSTIFICADA   = 'falta_justificada';
    case FALTA_INJUSTIFICADA = 'falta_injustificada';
    case CANCELADO           = 'cancelado';
}
```

---

## 📖 Definição dos Status

### ✅ 1. CONFIRMADO
**Quando:** Admin confirma que o aluno compareceu ao refeitório.

**Como acontece:**
- Admin vê o aluno na lista
- Admin clica no botão ✅ (verde)
- Sistema registra status `CONFIRMADO`

**Interface:**
- Badge cor: 🔵 Ciano (#17a2b8)
- Ação: Botão ✅ (verde)

---

### ⚠️ 2. FALTA_JUSTIFICADA
**Quando:** Aluno bolsista justifica sua falta.

**Como acontece (2 cenários):**

#### **Cenário A: Justificativa Antecipada**
1. Aluno sabe que não poderá ir
2. Aluno justifica **ANTES** do dia da refeição
3. Sistema registra `FALTA_JUSTIFICADA`

#### **Cenário B: Justificativa Posterior**
1. Aluno faltou sem justificar
2. Aluno justifica **DEPOIS** de ter faltado
3. Sistema muda status para `FALTA_JUSTIFICADA`

**Interface:**
- Badge cor: 🟡 Amarelo (#ffc107)
- Ação: Botão ⚠️ (amarelo) - Admin marca manualmente
- **OU** Automático: Sistema detecta justificativa do aluno

---

### ❌ 3. FALTA_INJUSTIFICADA
**Quando:** Aluno bolsista não compareceu E não justificou.

**Como acontece:**
1. Aluno estava vinculado ao dia
2. Aluno **NÃO** compareceu
3. Aluno **NÃO** justificou a falta
4. Sistema registra `FALTA_INJUSTIFICADA` (pode ser automático ou manual)

**Interface:**
- Badge cor: 🔴 Vermelho (#dc3545)
- Ação: Botão ❌ (vermelho) - Admin marca manualmente
- **OU** Automático: Sistema detecta falta ao final do dia

---

### 🚫 4. CANCELADO
**Quando:** Refeição foi cancelada.

**Como acontece:**
- Admin cancela a refeição do dia
- Sistema marca todas as presenças como `CANCELADO`

**Interface:**
- Badge cor: ⚫ Cinza (#6c757d)

---

## 🔄 Fluxos de Status

### **Fluxo 1: Aluno Comparece ✅**
```
┌──────────┐
│ PENDENTE │ (sem registro na tabela presencas)
└────┬─────┘
     │
     │ Admin clica no botão ✅
     ↓
┌────────────┐
│ CONFIRMADO │ 🔵 Badge Ciano
└────────────┘
```

---

### **Fluxo 2: Falta Justificada ⚠️**

#### **2A - Justificativa Antecipada:**
```
┌──────────┐
│ PENDENTE │
└────┬─────┘
     │
     │ Aluno justifica ANTES
     ↓
┌───────────────────┐
│ FALTA_JUSTIFICADA │ 🟡 Badge Amarelo
└───────────────────┘
```

#### **2B - Justificativa Posterior:**
```
┌──────────┐
│ PENDENTE │
└────┬─────┘
     │
     │ Dia passa, aluno faltou
     ↓
┌─────────────────────┐
│ FALTA_INJUSTIFICADA │ 🔴 Badge Vermelho
└──────────┬──────────┘
           │
           │ Aluno justifica DEPOIS
           ↓
┌───────────────────┐
│ FALTA_JUSTIFICADA │ 🟡 Badge Amarelo
└───────────────────┘
```

---

### **Fluxo 3: Falta Injustificada ❌**
```
┌──────────┐
│ PENDENTE │
└────┬─────┘
     │
     │ Dia passa, aluno NÃO compareceu E NÃO justificou
     ↓
┌─────────────────────┐
│ FALTA_INJUSTIFICADA │ 🔴 Badge Vermelho
└─────────────────────┘
```

**Pode ser:**
- **Manual:** Admin clica no botão ❌
- **Automático:** Sistema marca ao final do dia

---

### **Fluxo 4: Cancelamento 🚫**
```
┌────────────────┐
│ Qualquer Status│
└────────┬───────┘
         │
         │ Admin cancela refeição
         ↓
┌──────────┐
│CANCELADO │ ⚫ Badge Cinza
└──────────┘
```

---

## 🎨 Cores e Ações na Interface

| Status | Badge | Botão Admin | Automático? |
|--------|-------|-------------|-------------|
| **CONFIRMADO** | 🔵 Ciano | ✅ Verde | Não |
| **FALTA_JUSTIFICADA** | 🟡 Amarelo | ⚠️ Amarelo | Pode ser |
| **FALTA_INJUSTIFICADA** | 🔴 Vermelho | ❌ Vermelho | Pode ser |
| **CANCELADO** | ⚫ Cinza | 🚫 (raro) | Não |
| **Pendente** | ⚫ Cinza | - | Sim (ausência de registro) |

---

## 📊 Estatísticas na Tela

```
┌───────┬─────────────┬───────────┬──────────────┬─────────────────┐
│ Total │ Confirmados │ Pendentes │ Faltas Just. │ Faltas Injust.  │
└───────┴─────────────┴───────────┴──────────────┴─────────────────┘
```

### Contagem:
- **Total**: Bolsistas cadastrados para o dia
- **Confirmados**: Status = `confirmado`
- **Pendentes**: Sem registro na tabela `presencas`
- **Faltas Justificadas**: Status = `falta_justificada`
- **Faltas Injustificadas**: Status = `falta_injustificada`

---

## 🔍 Regras de Negócio

### ✅ **Presença Confirmada**
```sql
-- Admin clica no botão ✅
INSERT INTO presencas (user_id, refeicao_id, status_da_presenca, validado_por, validado_em)
VALUES (1, 123, 'confirmado', admin_id, NOW());
```

### ⚠️ **Falta Justificada (Manual)**
```sql
-- Admin clica no botão ⚠️
INSERT INTO presencas (user_id, refeicao_id, status_da_presenca, validado_por, validado_em)
VALUES (1, 123, 'falta_justificada', admin_id, NOW());
```

### ⚠️ **Falta Justificada (Automática - Antecipada)**
```sql
-- Aluno cria justificativa ANTES do dia
INSERT INTO justificativas (user_id, data_justificada, motivo, ...)
VALUES (1, '2026-01-08', 'Consulta médica', ...);

-- Sistema cria presença automaticamente
INSERT INTO presencas (user_id, refeicao_id, status_da_presenca, registrado_em)
VALUES (1, 123, 'falta_justificada', NOW());
```

### ⚠️ **Falta Justificada (Automática - Posterior)**
```sql
-- Aluno já tem falta injustificada
UPDATE presencas 
SET status_da_presenca = 'falta_justificada', 
    updated_at = NOW()
WHERE user_id = 1 AND refeicao_id = 123;
```

### ❌ **Falta Injustificada (Manual)**
```sql
-- Admin clica no botão ❌
INSERT INTO presencas (user_id, refeicao_id, status_da_presenca, validado_por, validado_em)
VALUES (1, 123, 'falta_injustificada', admin_id, NOW());
```

### ❌ **Falta Injustificada (Automática - Job/Cron)**
```php
// Job roda ao final do dia
$bolsistas = User::where('bolsista', true)
    ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', today()->dayOfWeek))
    ->get();

foreach ($bolsistas as $bolsista) {
    $temPresenca = Presenca::where('user_id', $bolsista->id)
        ->where('refeicao_id', $refeicao->id)
        ->exists();
    
    if (!$temPresenca) {
        // Verifica se tem justificativa
        $temJustificativa = Justificativa::where('user_id', $bolsista->id)
            ->where('data_justificada', today())
            ->exists();
        
        if (!$temJustificativa) {
            // Marca falta injustificada
            Presenca::create([
                'user_id' => $bolsista->id,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::FALTA_INJUSTIFICADA,
                'registrado_em' => now(),
            ]);
        }
    }
}
```

---

## 🎯 Ações na Interface

### Tela: bolsistas-dia.html

#### **Botões por Aluno:**
```html
<td class="actions">
    <!-- Confirmar Presença -->
    <button class="btn btn-success" onclick="confirmarPresenca(userId)">
        ✅ Confirmar
    </button>
    
    <!-- Marcar Falta Injustificada -->
    <button class="btn btn-danger" onclick="marcarFalta(userId, false)">
        ❌ Falta
    </button>
    
    <!-- Marcar Falta Justificada -->
    <button class="btn btn-warning" onclick="marcarFalta(userId, true)">
        ⚠️ Justificada
    </button>
</td>
```

### Comportamento dos Botões:

#### ✅ **Botão Confirmar**
- **Ação**: Cria presença com status `CONFIRMADO`
- **Desabilita**: Quando status === 'confirmado'
- **Endpoint**: `POST /api/v1/admin/bolsistas/{id}/confirmar-presenca`

#### ❌ **Botão Falta**
- **Ação**: Cria presença com status `FALTA_INJUSTIFICADA`
- **Parâmetro**: `justificada: false`
- **Endpoint**: `POST /api/v1/admin/bolsistas/{id}/marcar-falta`

#### ⚠️ **Botão Justificada**
- **Ação**: Cria presença com status `FALTA_JUSTIFICADA`
- **Parâmetro**: `justificada: true`
- **Endpoint**: `POST /api/v1/admin/bolsistas/{id}/marcar-falta`

---

## 🔔 Notificações e Limites

### **Limite de Faltas**
```php
// User.php
protected $fillable = [
    // ...
    'limite_faltas_mes', // Default: 3
];
```

### **Contagem de Faltas no Mês**
```php
$faltasDoMes = Presenca::where('user_id', $userId)
    ->whereIn('status_da_presenca', [
        StatusPresenca::FALTA_INJUSTIFICADA,
        // Faltas justificadas podem ou não contar, depende da regra
    ])
    ->whereMonth('created_at', now()->month)
    ->count();

if ($faltasDoMes >= $user->limite_faltas_mes) {
    // Desligar bolsista ou notificar
    $user->update([
        'desligado' => true,
        'desligado_em' => now(),
        'desligado_motivo' => 'Excesso de faltas injustificadas',
    ]);
}
```

---

## 📋 Tabela de Decisão

| Situação | Status Resultante | Como |
|----------|-------------------|------|
| Aluno comparece | CONFIRMADO | Admin clica ✅ |
| Aluno justifica antes | FALTA_JUSTIFICADA | Automático ou ⚠️ |
| Aluno falta sem justificar | FALTA_INJUSTIFICADA | Automático ou ❌ |
| Aluno justifica depois | FALTA_JUSTIFICADA | Muda de INJUSTIFICADA → JUSTIFICADA |
| Refeição cancelada | CANCELADO | Admin cancela |

---

## ✅ Resumo dos Status

### **São apenas 4 status:**

1. ✅ **CONFIRMADO** - Presença confirmada pelo admin
2. ⚠️ **FALTA_JUSTIFICADA** - Falta com justificativa (antes ou depois)
3. ❌ **FALTA_INJUSTIFICADA** - Falta sem justificativa
4. 🚫 **CANCELADO** - Refeição cancelada

### **+ 1 estado virtual:**
- ⚪ **Pendente** - Sem registro na tabela (mostrado na interface)

---

## 🎯 Implementação Completa

### ✅ Já Implementado:
- [x] Enum com 4 status corretos
- [x] Controller confirma presença (CONFIRMADO)
- [x] Controller marca faltas (JUSTIFICADA e INJUSTIFICADA)
- [x] HTML com 3 botões (✅, ❌, ⚠️)
- [x] Estatísticas corretas
- [x] Coluna "Dias da Semana"

### 🔄 Próximas Implementações (opcional):
- [ ] Job/Cron para marcar faltas injustificadas automaticamente
- [ ] Sistema de justificativas (tela para aluno)
- [ ] Notificação ao atingir limite de faltas
- [ ] Relatório de faltas por bolsista

---

**Data:** 2026-01-05  
**Status:** ✅ **DOCUMENTAÇÃO COMPLETA**  
**Responsável:** GitHub Copilot

