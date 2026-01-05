# ✅ SISTEMA DE PRESENÇA - Funcionamento Completo

## 🎯 Conceitos Fundamentais

### **Lista do Dia = Alunos com direito à refeição NAQUELE dia da semana**

O sistema agora filtra corretamente:

```
Segunda (dia 1):
- ✅ João (cadastrado para segunda) → APARECE
- ❌ Maria (cadastrada para terça) → NÃO APARECE
```

---

## 📊 Estados de Presença (Status)

### **1️⃣ Sem Registro** (badge cinza)
```
Status: null
Significado: Aluno tem direito à refeição mas ainda não confirmou presença
Visual: "Sem registro"
Checkbox: ☐ Desmarcado (pode marcar para validar)
```

### **2️⃣ Confirmado** (badge amarelo)
```
Status: 'confirmado'  
Significado: Aluno confirmou que vai comer
Visual: "Confirmado"
Checkbox: ☐ Desmarcado (pode marcar para validar)
```

### **3️⃣ Validado** (badge verde)
```
Status: 'validado'
Significado: Admin marcou presença (aluno comeu)
Visual: "✅ Validado"
Checkbox: ☑ Marcado (presença confirmada)
```

### **4️⃣ Falta Justificada** (badge cinza)
```
Status: 'falta_justificada'
Significado: Aluno não veio mas justificou
Visual: "Falta Justificada"
Checkbox: ☐ Desabilitado
Botão Falta: ✓ (já marcada)
```

### **5️⃣ Falta Injustificada** (badge vermelho)
```
Status: 'falta_injustificada'
Significado: Aluno não veio e não justificou
Visual: "❌ Falta"
Checkbox: ☐ Desabilitado
Botão Falta: ✓ (já marcada)
```

---

## 🔄 Fluxo Completo do Dia

### **Manhã (08:00):**

```
1. Admin abre lista do dia (05/01/2026 - Domingo)
2. Sistema busca alunos cadastrados para DOMINGO
3. Lista mostra:
   - João → Status: Sem registro (não confirmou ainda)
   - Maria → Status: Confirmado (confirmou ontem)
   - Pedro → Status: Falta Justificada (avisou que não viria)
```

### **Durante o Almoço (11:30 - 13:00):**

```
1. Alunos vão chegando
2. Admin marca checkbox ao lado do nome
3. Status muda para "✅ Validado"
4. Presença registrada!
```

### **Ao Fim do Almoço (13:00):**

```
1. Admin vê quem ficou sem validar
2. Para cada ausente:
   - Se justificou → Já está com "Falta Justificada"
   - Se NÃO justificou → Admin clica "❌" → "Falta Injustificada"
```

---

## 📋 Tabela Visual

```
┌─────────────┬───────────┬──────────────┬─────────────────────┬──────────┬────────┐
│ Nome        │ Matrícula │ Refeição     │ Status              │ Presente?│ Falta  │
├─────────────┼───────────┼──────────────┼─────────────────────┼──────────┼────────┤
│ João Silva  │ 20241234  │ Almoço 05/01 │ Sem registro        │    ☐     │  ❌    │
│ Maria Lima  │ 20241235  │ Almoço 05/01 │ Confirmado          │    ☐     │  ❌    │
│ Pedro Gomes │ 20241236  │ Almoço 05/01 │ ✅ Validado         │    ☑     │        │
│ Ana Costa   │ 20241237  │ Almoço 05/01 │ Falta Justificada   │    ☐     │  ✓     │
│ Carlos Souza│ 20241238  │ Almoço 05/01 │ ❌ Falta            │    ☐     │  ✓     │
└─────────────┴───────────┴──────────────┴─────────────────────┴──────────┴────────┘
```

---

## 🎯 Sistema de Justificativas

### **Justificativa ANTECIPADA (antes da refeição):**

**Como funciona:**
1. Aluno entra no sistema e informa falta antecipada
2. Sistema cria registro: `status = 'falta_justificada'`
3. Na lista do dia, já aparece com badge "Falta Justificada"
4. Admin não precisa fazer nada

**Implementação (futura):**
```php
// Endpoint: POST /api/v1/estudante/justificar-falta
public function justificarFaltaAntecipada(Request $request)
{
    $validated = $request->validate([
        'data' => 'required|date',
        'turno' => 'required|in:almoco,jantar',
        'motivo' => 'required|string',
    ]);

    // Buscar refeição
    $refeicao = Refeicao::where('data_do_cardapio', $validated['data'])
        ->where('turno', $validated['turno'])
        ->firstOrFail();

    // Criar presença com falta justificada
    Presenca::create([
        'user_id' => auth()->id(),
        'refeicao_id' => $refeicao->id,
        'status_da_presenca' => 'falta_justificada',
        'registrado_em' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Falta justificada com sucesso.',
    ]);
}
```

---

### **Justificativa POSTERIOR (depois da refeição):**

**Como funciona:**
1. Admin marca falta injustificada (botão ❌)
2. Status: `'falta_injustificada'`
3. Depois, aluno justifica no sistema
4. Admin ou sistema automático muda para `'falta_justificada'`

**Implementação (futura):**
```php
// Endpoint: POST /api/v1/admin/presencas/{id}/aceitar-justificativa
public function aceitarJustificativa($id)
{
    $presenca = Presenca::findOrFail($id);
    
    $presenca->update([
        'status_da_presenca' => 'falta_justificada',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Justificativa aceita.',
    ]);
}
```

---

## 🔍 Filtros e Lógica

### **Por que a lista muda por dia?**

```php
// Controller busca apenas alunos cadastrados para aquele dia da semana:
$diaDaSemana = Carbon::parse($data)->dayOfWeek; // 0=Dom, 1=Seg, ...

$bolsistas = User::where('bolsista', true)
    ->whereHas('diasSemana', function($q) use ($diaDaSemana) {
        $q->where('dia_semana', $diaDaSemana);
    })
    ->get();
```

**Exemplo:**
- **Domingo (dia 0):** Mostra apenas alunos cadastrados para domingo
- **Segunda (dia 1):** Mostra apenas alunos cadastrados para segunda
- **Terça (dia 2):** Mostra apenas alunos cadastrados para terça

---

## 📊 Estatísticas

```javascript
{
  "total_bolsistas": 10,        // Total de alunos com direito NESTE dia
  "presentes": 5,               // Validados (checkbox marcado)
  "ausentes": 5,                // Sem validação
  "confirmados": 3,             // Status: 'confirmado'
  "validados": 5,               // Status: 'validado'
  "faltas_justificadas": 2,     // Status: 'falta_justificada'
  "faltas_injustificadas": 3    // Status: 'falta_injustificada'
}
```

---

## 🎯 Casos de Uso

### **Caso 1: Aluno avisa que não virá (antecipado)**

**Antes da refeição:**
1. Aluno justifica no sistema (futuro)
2. Sistema cria: `status = 'falta_justificada'`
3. Admin abre lista → Já aparece "Falta Justificada"

### **Caso 2: Aluno não vem e não avisa**

**Durante a refeição:**
1. Admin espera até o fim
2. Aluno não aparece
3. Admin clica "❌" → Status: `'falta_injustificada'`

**Depois:**
4. Aluno justifica (futuro)
5. Admin aceita → Status muda para `'falta_justificada'`

### **Caso 3: Fluxo normal**

1. Aluno confirma presença → Status: `'confirmado'`
2. Aluno chega → Admin marca ☐ → Status: `'validado'`

---

## ✅ Correções Implementadas

### **1️⃣ Filtro por Dia da Semana**
- ✅ Agora mostra apenas alunos cadastrados para aquele dia
- ✅ Segunda mostra alunos de segunda, Terça mostra de terça, etc.

### **2️⃣ Coluna Refeição**
- ✅ Mostra "Almoço - 05/01/2026" corretamente
- ✅ Dados vêm da API, não do filtro

### **3️⃣ Status**
- ✅ Aparece uma vez só (não duplicado)
- ✅ Mostra "Sem registro" quando não há presença
- ✅ Mostra status correto baseado no banco

### **4️⃣ Checkbox e Botão Falta**
- ✅ Checkbox marcado = Validado
- ✅ Checkbox desabilitado = Já tem falta
- ✅ Botão "❌" funciona para marcar falta

---

## 📁 Arquivos Modificados

- ✅ `app/Http/Controllers/api/v1/Admin/PresencaController.php`
  - Filtro por dia da semana
  - Adiciona `refeicao` aos dados retornados

- ✅ `public/lista-presencas-dia.html`
  - Usa `item.refeicao` da API
  - Trata `status = null` (sem registro)
  - Badge "Sem registro" para casos sem presença

---

## 🎉 Status Final

✅ **Lista mostra apenas alunos do dia**  
✅ **Coluna Refeição correta**  
✅ **Status aparece uma vez**  
✅ **Sistema de justificativas planejado**  
✅ **Fluxo completo documentado**  

---

**Data:** 05/01/2026 (Domingo)  
**Status:** ✅ FUNCIONANDO CORRETAMENTE

