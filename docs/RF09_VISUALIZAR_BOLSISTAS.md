# RF09 – Visualizar Bolsistas do Dia

## Descrição
Funcionalidade que permite ao admin visualizar a lista de bolsistas por dia e por turno, podendo confirmar presença ou registrar falta.

> **Nota:** Bolsistas cadastrados para um dia têm direito a ambas as refeições (almoço e jantar).

## Status de Presença (StatusPresenca Enum)

| Status | Descrição |
|--------|-----------|
| `confirmado` | Estudante confirmou que vai comparecer |
| `validado` | Presença validada pelo admin (estudante compareceu) |
| `falta_justificada` | Falta com justificativa aceita |
| `falta_injustificada` | Falta sem justificativa |
| `cancelado` | Presença cancelada |

## Endpoints Implementados

### 1. Bolsistas do Dia
**GET** `/api/v1/admin/bolsistas/dia`

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| data | string | Não | Data no formato `Y-m-d` ou `d/m/Y`. Default: hoje |
| turno | string | Não | `almoco` ou `jantar` |

**Resposta:**
```json
{
    "success": true,
    "data": [
        {
            "user_id": 1,
            "matricula": "20231001",
            "nome": "João Silva",
            "curso": "ADS",
            "turno_aluno": "matutino",
            "is_bolsista": true,
            "presenca": {
                "id": 1,
                "status": "validado",
                "validado_em": "05/01/2026 12:30"
            },
            "status_presenca": "validado"
        }
    ],
    "meta": {
        "data": "05/01/2026",
        "dia_semana": 1,
        "dia_semana_texto": "segunda-feira",
        "turno_filtrado": "almoco",
        "total_bolsistas": 10
    },
    "stats": {
        "total": 10,
        "presentes": 8,
        "confirmados": 1,
        "pendentes": 1,
        "faltas": 0
    }
}
```

---

### 2. Todos os Bolsistas
**GET** `/api/v1/admin/bolsistas`

**Resposta:**
```json
{
    "success": true,
    "data": [
        {
            "user_id": 1,
            "matricula": "20231001",
            "nome": "João Silva",
            "email": "joao@email.com",
            "curso": "ADS",
            "turno": "matutino",
            "is_bolsista": true,
            "desligado": false,
            "dias_semana": [1, 2, 3, 4, 5],
            "dias_semana_texto": "Segunda, Terça, Quarta, Quinta, Sexta"
        }
    ],
    "total": 15
}
```

---

### 3. Estudantes por Turno
**GET** `/api/v1/admin/estudantes/turno`

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| data | string | Não | Data no formato `Y-m-d` ou `d/m/Y`. Default: hoje |
| turno | string | Não | `almoco` ou `jantar` |
| apenas_ativos | boolean | Não | Filtrar apenas não desligados. Default: true |

---

### 4. Confirmar Presença de Bolsista
**POST** `/api/v1/admin/bolsistas/{userId}/confirmar-presenca`

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| data | string | Não | Data da refeição. Default: hoje |
| turno | string | **Sim** | `almoco` ou `jantar` |

**Resposta de sucesso:**
```json
{
    "success": true,
    "message": "Presença confirmada com sucesso.",
    "data": {
        "presenca_id": 1,
        "usuario": "João Silva",
        "matricula": "20231001",
        "refeicao": {
            "id": 5,
            "data": "05/01/2026",
            "turno": "almoco"
        },
        "validado_em": "05/01/2026 12:30"
    }
}
```

---

### 5. Marcar Falta de Bolsista
**POST** `/api/v1/admin/bolsistas/{userId}/marcar-falta`

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| data | string | Não | Data da refeição. Default: hoje |
| turno | string | **Sim** | `almoco` ou `jantar` |
| justificada | boolean | Não | Se a falta é justificada. Default: false |

---

### 6. Confirmar Presença em Lote
**POST** `/api/v1/admin/bolsistas/confirmar-lote`

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| user_ids | array | **Sim** | Array de IDs de usuários |
| data | string | Não | Data da refeição. Default: hoje |
| turno | string | **Sim** | `almoco` ou `jantar` |

**Exemplo de requisição:**
```json
{
    "user_ids": [1, 2, 3, 4, 5],
    "turno": "almoco",
    "data": "2026-01-05"
}
```

---

## Fluxo de Uso

1. Admin acessa `/api/v1/admin/bolsistas/dia?turno=almoco` para ver bolsistas do dia
2. Para cada bolsista, pode:
   - Confirmar presença via `POST /bolsistas/{userId}/confirmar-presenca`
   - Marcar falta via `POST /bolsistas/{userId}/marcar-falta`
3. Para confirmar múltiplos de uma vez, usa `POST /bolsistas/confirmar-lote`

---

## Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 400 | Parâmetros inválidos (ex: turno não informado) |
| 403 | Usuário não tem permissão (ex: não é bolsista, dia não cadastrado) |
| 404 | Usuário ou refeição não encontrado |
| 409 | Conflito (ex: presença já confirmada) |

