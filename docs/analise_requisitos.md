# Análise de Conformidade: Requisitos Funcionais vs Implementação

> **Projeto:** Sistema de Gestão do Restaurante Institucional (RI IFBA)
> **Data:** Janeiro 2026
> **Versão:** 1.0

---

## Resumo Executivo

| Requisito | Status | Ação Necessária |
|-----------|--------|-----------------|
| RF08 | ✅ Conforme | Nenhuma |
| RF09 | ✅ Conforme | Nenhuma |
| RF10 | ⚠️ Revisão | Ajustar texto |
| RF11 | ✅ Conforme | Nenhuma |
| RF12 | ✅ Conforme | Nenhuma |
| RF13 | ✅ Conforme | Nenhuma |
| RF14 | ⚠️ Revisão | Ajustar texto |
| RF15 | ⚠️ Revisão | Ajustar texto |

---

## Análise Detalhada

### RF08 – Manter cardápio ✅

**Texto original:**
> Como administrador do RI, desejo cadastrar e atualizar o cardápio semanal (comum e vegetariano) para disponibilizar as informações aos estudantes, de forma manual ou através de upload de dados via planilha excel ou csv.

**Status:** ✅ Conforme

**Implementação:**
- `CardapioController.store()` - Cadastro manual
- `CardapioController.update()` - Atualização
- `CardapioController.import()` - Upload via Excel/CSV
- Suporta cardápio comum e vegetariano

---

### RF09 – Visualizar bolsistas do dia ✅

**Texto original:**
> Como administrador do RI, desejo visualizar em tempo a lista de bolsistas para o dia para ter controle da demanda esperada.

**Status:** ✅ Conforme

**Implementação:**
- `BolsistaController.bolsistasDoDia()` - Lista bolsistas elegíveis por turno

---

### RF10 – Validar justificativas ⚠️

**Texto original:**
> Como administrador do RI, desejo acessar as justificativas de faltas dos bolsistas.

**Status:** ⚠️ Texto subestima a funcionalidade

**Implementação real:**
- `JustificativaController.index()` - Listar justificativas
- `JustificativaController.aprovar()` - Aprovar justificativa
- `JustificativaController.rejeitar()` - Rejeitar justificativa
- `JustificativaController.downloadAnexo()` - Baixar comprovantes

**Texto sugerido:**
> Como administrador do RI, desejo **validar (aprovar ou rejeitar)** as justificativas de faltas dos bolsistas, podendo visualizar detalhes, histórico e baixar os anexos comprobatórios.

---

### RF11 – Acessar dashboard de utilização ✅

**Texto original:**
> Como administrador do RI, desejo acessar informações (dashboard), com estatísticas, da utilização (taxa de presença, faltas justificadas/injustificadas, extras atendidos) para análise e planejamento.

**Status:** ✅ Conforme

**Implementação:**
- `DashboardController.index()` - Dashboard completo
- `DashboardController.resumo()` - Resumo geral
- `DashboardController.taxaPresenca()` - Taxa de presença
- `DashboardController.faltas()` - Faltas por tipo
- `DashboardController.extras()` - Extras atendidos
- `DashboardController.evolucao()` - Evolução mensal
- `DashboardController.faltosos()` - Top faltosos

---

### RF12 – Gerar relatórios ✅

**Texto original:**
> Como administrador do RI, desejo gerar relatórios por período para prestação de contas, controle e identificação de padrões.

**Status:** ✅ Conforme

**Implementação:**
- `RelatorioController` - Geração de relatórios
- `RelatorioValidacaoController` - Relatórios de validação
- `RelatorioService` - Lógica de relatórios
- Exportação para Excel

---

### RF13 – Validar acesso por QR Code ou manualmente ✅

**Texto original:**
> Como administrador do RI, desejo validar a entrada dos estudantes através da leitura de QR Code individual ou busca por matrícula para controle de acesso no momento da refeição.

**Status:** ✅ Conforme

**Implementação:**
- `BolsistaController.confirmarPorQrCode()` - Validação por QR Code
- `BolsistaController.buscarParaConfirmacao()` - Busca manual por matrícula/nome
- `BolsistaController.confirmarPresenca()` - Confirmação de presença

---

### RF14 – Gerenciar usuários ⚠️

**Texto original:**
> Como administrador do RI, desejo cadastrar, editar e remover usuários do sistema (bolsistas e extras) para manter a base de dados atualizada.

**Status:** ⚠️ Terminologia incorreta

**Implementação real:**
- Sistema usa **soft delete** (desativação), não remoção permanente
- Perfis são `estudante` e `admin`, não "bolsistas e extras"
- Bolsista é um **atributo** do estudante, não um perfil separado

**Texto sugerido:**
> Como administrador do RI, desejo cadastrar, editar e **desativar** usuários do sistema (estudantes e administradores) para manter a base de dados atualizada. O status de bolsista é definido automaticamente com base na lista de matrículas aprovadas.

---

### RF15 – Importar lista de bolsistas por turno ⚠️

**Texto original:**
> Como administrador do RI, desejo anexar/inserir a lista de bolsistas por turno, importando isso via excel ou csv.

**Status:** ⚠️ Texto não reflete a arquitetura real

**Implementação real:**
- A importação cadastra **matrículas aprovadas** na tabela `bolsistas`
- Quando um estudante se cadastra, o sistema verifica se sua matrícula está na lista
- A flag `bolsista` é definida automaticamente, não importando usuários diretamente

**Texto sugerido:**
> Como administrador do RI, desejo importar a lista de **matrículas aprovadas** para o programa de bolsas, por turno de refeição (almoço/jantar), via Excel ou CSV. O sistema verificará automaticamente se cada estudante cadastrado possui matrícula na lista aprovada para definir seu status de bolsista.

---

## Arquivos de Implementação

| Requisito | Controller Principal |
|-----------|---------------------|
| RF08 | `CardapioController.php` |
| RF09 | `BolsistaController.php` |
| RF10 | `JustificativaController.php` |
| RF11 | `DashboardController.php` |
| RF12 | `RelatorioController.php` |
| RF13 | `BolsistaController.php` |
| RF14 | `UserController.php` |
| RF15 | `BolsistaAprovadoController.php` |
