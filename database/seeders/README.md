# 🌱 Seeders - Sistema RI IFBA

## 📋 Ordem de Execução

Executados automaticamente via `DatabaseSeeder`:

1. **BolsistasAprovadosSeeder** - Lista de bolsistas aprovados
2. **UserSeeder** - Usuários (admin, bolsistas, não-bolsistas)
3. **CardapioMensalSeeder** ⭐ - Cardápios do mês (seg-sex)
4. **UsuarioDiaSemanaSeeder** - Vincula bolsistas aos dias
5. **PresencaSeeder** - Presenças de exemplo
6. **JustificativaSeeder** - Justificativas de faltas
7. **FilaExtraSeeder** - Inscrições na fila extra

---

## ⭐ Seeder Principal: CardapioMensalSeeder

**O que faz:**
- Cria ~22 cardápios (dias úteis do mês inteiro)
- Pula finais de semana automaticamente (sábado e domingo)
- Refeições criadas automaticamente pelo model `Cardapio`
- Verifica duplicatas antes de criar

**Importante:**
- ✅ Use `'turnos' => ['almoco', 'jantar']` ao criar cardápios
- ❌ NUNCA crie refeições manualmente (o model faz isso)

---

## 🚀 Como Executar

### Popular o banco completo:
```bash
php artisan migrate:fresh --seed
```

### Executar apenas um seeder específico:
```bash
php artisan db:seed --class=CardapioMensalSeeder
```

### Resetar e popular (via script):
```bash
./reset-database.sh
```

---

## 📊 Dados Criados

| Seeder | Quantidade | Descrição |
|--------|-----------|-----------|
| BolsistasAprovadosSeeder | 25 registros | 20 vinculados + 5 pendentes |
| UserSeeder | 27 usuários | 1 admin + 20 bolsistas + 5 não-bolsistas + 1 desligado |
| CardapioMensalSeeder | ~22 cardápios | Apenas dias úteis |
| - | ~44 refeições | Criadas automaticamente (2 por cardápio) |
| UsuarioDiaSemanaSeeder | ~71 vínculos | Média 3-4 dias por bolsista |
| PresencaSeeder | Várias | Últimos 4 dias de refeições |
| JustificativaSeeder | Várias | Exemplos de justificativas |
| FilaExtraSeeder | Várias | Não-bolsistas na fila |

---

## 🗑️ Seeders Obsoletos (DELETADOS)

- ❌ **CardapioSeeder** - Criava apenas 5 cardápios (1 semana)
- ❌ **RefeicaoSeeder** - Criava refeições manualmente (causava duplicação)

**Motivo:** As refeições são criadas automaticamente pelo model `Cardapio` via `boot()`.

---

## 📚 Documentação Completa

Veja: [`docs/SEEDERS.md`](../docs/SEEDERS.md)

---

## 🐛 Troubleshooting

### Erro: "duplicate key value violates unique constraint"
**Solução:** Não crie refeições manualmente. Use `'turnos' => ['almoco', 'jantar']`

### Erro: "Cannot access offset of type Carbon"
**Solução:** Já foi corrigido no PresencaSeeder (converte Carbon para string)

### Cardápios em finais de semana
**Solução:** Já foi corrigido - usa `isSaturday()` e `isSunday()`

---

**Última atualização:** 14/01/2026
**Status:** ✅ Funcionando corretamente

