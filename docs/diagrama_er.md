# Modelagem do Banco de Dados - RI IFBA

## Diagrama Entidade-Relacionamento (ER)

```mermaid
erDiagram
    users ||--o{ presencas : "tem"
    users ||--o{ justificativas : "envia"
    users ||--o{ filas_extras : "inscreve"
    users ||--o{ usuario_dias_semana : "tem"
    users ||--o{ cardapios : "cria"
    users ||--o| bolsistas : "vincula"
    
    cardapios ||--o{ refeicoes : "possui"
    
    refeicoes ||--o{ presencas : "registra"
    refeicoes ||--o{ justificativas : "relaciona"
    refeicoes ||--o{ filas_extras : "gerencia"

    users {
        bigint id PK
        string matricula UK
        string nome
        string email UK
        string password
        enum perfil "estudante|admin"
        boolean bolsista
        int limite_faltas_mes
        boolean desligado
        timestamp desligado_em
        text desligado_motivo
        string curso
        string turno
        string preferencia_alimentar
        string foto_perfil
        timestamp created_at
        timestamp updated_at
    }

    cardapios {
        bigint id PK
        date data_do_cardapio UK
        string prato_principal_ptn01
        string prato_principal_ptn02
        string guarnicao
        string acompanhamento_01
        string acompanhamento_02
        string salada
        string ovo_lacto_vegetariano
        string suco
        string sobremesa
        bigint criado_por FK
        timestamp criado_em
        timestamp atualizado_em
    }

    refeicoes {
        bigint id PK
        bigint cardapio_id FK
        date data_do_cardapio
        enum turno "almoco|jantar"
        int capacidade
        timestamp criado_em
        timestamp atualizado_em
    }

    presencas {
        bigint id PK
        bigint user_id FK
        bigint refeicao_id FK
        enum status_da_presenca "confirmado|validado|presente|falta_justificada|falta_injustificada|cancelado"
        timestamp validado_em
        bigint validado_por FK
        timestamp registrado_em
        timestamp created_at
        timestamp updated_at
    }

    justificativas {
        bigint id PK
        bigint user_id FK
        bigint refeicao_id FK
        enum tipo "antecipada|posterior"
        text motivo
        string anexo
        enum status "pendente|aprovada|rejeitada"
        bigint avaliado_por FK
        timestamp avaliado_em
        text motivo_rejeicao
        timestamp enviado_em
        timestamp created_at
        timestamp updated_at
    }

    filas_extras {
        bigint id PK
        bigint user_id FK
        bigint refeicao_id FK
        enum status_fila_extras "inscrito|aprovado|rejeitado"
        timestamp inscrito_em
        timestamp created_at
        timestamp updated_at
    }

    usuario_dias_semana {
        bigint user_id PK_FK
        tinyint dia_semana PK "0-6"
    }

    bolsistas {
        bigint id PK
        string matricula UK
        string nome
        string curso
        string turno
        json dias_semana
        boolean ativo
        bigint user_id FK
        timestamp vinculado_em
        timestamp created_at
        timestamp updated_at
    }
```

---

## Resumo das Tabelas

| Tabela | Descrição | Registros Esperados |
|--------|-----------|---------------------|
| `users` | Usuários (estudantes e admins) | ~500 |
| `cardapios` | Cardápios diários | ~250/ano |
| `refeicoes` | Refeições (almoço/jantar por dia) | ~500/ano |
| `presencas` | Registro de presenças | ~50.000/ano |
| `justificativas` | Justificativas de faltas | ~500/ano |
| `filas_extras` | Fila para vagas extras | ~1.000/ano |
| `usuario_dias_semana` | Dias de refeição do bolsista | ~2.500 |
| `bolsistas` | Lista de matrículas aprovadas | ~500 |

---

## Relacionamentos Principais

| Origem | Destino | Cardinalidade | Descrição |
|--------|---------|---------------|-----------|
| users | presencas | 1:N | Usuário tem várias presenças |
| users | justificativas | 1:N | Usuário envia justificativas |
| users | filas_extras | 1:N | Usuário se inscreve em filas |
| cardapios | refeicoes | 1:N | Cardápio tem 1-2 refeições |
| refeicoes | presencas | 1:N | Refeição tem várias presenças |
| bolsistas | users | 1:1 | Bolsista vincula a um usuário |
