# Justificativa Técnica: Modelagem do Banco de Dados

## Sistema: Refeitório Institucional IFBA
**Autor:** Emmanuel de Jesus  
**Data:** Janeiro/2026

---

## 1. Visão Geral

Este documento justifica as decisões de modelagem do banco de dados do sistema RI IFBA, incluindo a estrutura das tabelas e campos utilizados.

---

## 2. Tabela `bolsistas`: Área de Staging para Importação

### 2.1 Propósito

A tabela `bolsistas` funciona como uma **área de staging** (preparação) para o processo de importação de estudantes aprovados como bolsistas do programa de alimentação.

### 2.2 Fluxo de Dados

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  Planilha Excel │────▶│ Tabela bolsistas│────▶│  Tabela users   │
│  (Importação)   │     │    (Staging)    │     │   (Produção)    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                              │                        ▲
                              │   Vínculo após         │
                              └─── cadastro ───────────┘
```

### 2.3 Campos Duplicados: nome, curso, turno

| Campo | Tabela `bolsistas` | Tabela `users` |
|-------|-------------------|----------------|
| nome | Dados da importação (referência) | Dados oficiais do cadastro |
| curso | Dados da importação (referência) | Dados oficiais do cadastro |
| turno | Dados da importação (referência) | Dados oficiais do cadastro |

**Justificativa:** Os campos em `bolsistas` armazenam os dados como vieram na planilha de importação, servindo para:

1. **Conferência**: Permite verificar se os dados que o estudante informou no cadastro conferem com a lista oficial
2. **Auditoria**: Mantém registro histórico do que foi importado originalmente
3. **Pré-cadastro**: Permite ao sistema ter informações antes do estudante se cadastrar

Após o vínculo (`bolsistas.user_id`), os dados oficiais passam a ser os da tabela `users`.

### 2.4 Alternativas Consideradas

| Alternativa | Motivo da Rejeição |
|-------------|-------------------|
| Remover campos duplicados | Perderia capacidade de auditoria e pré-verificação |
| Unificar em uma tabela | Complicaria o fluxo de importação quando usuário não existe |

---

## 3. Campos Padrão do Laravel

### 3.1 `email_verified_at`

Campo padrão do framework Laravel para verificação de email. Mantido para:
- Compatibilidade com componentes do Laravel
- Possibilidade de implementação futura
- Evitar modificações no comportamento padrão do Eloquent

### 3.2 `remember_token`

Campo padrão para funcionalidade "Lembrar-me" do Laravel. Mantido pelos mesmos motivos acima.

---

## 4. Campo `matricula` como STRING

A matrícula do estudante (ex: `20212160036`) é armazenada como `VARCHAR(20)` ao invés de `INTEGER` pelos seguintes motivos:

1. **Zeros à esquerda**: Matrículas podem começar com zero
2. **Natureza identificadora**: Matrícula é um identificador, não um valor numérico para cálculos
3. **Flexibilidade**: Permite futuras matrículas alfanuméricas
4. **Padrão acadêmico**: Sistemas como SIGAA e Q-Acadêmico usam o mesmo padrão

---

## 5. Conclusão

A modelagem atual do banco de dados segue boas práticas de desenvolvimento, mantendo compatibilidade com o framework Laravel e atendendo aos requisitos funcionais do sistema. Os aparentes campos "duplicados" têm propósito técnico definido e não representam redundância desnecessária.
