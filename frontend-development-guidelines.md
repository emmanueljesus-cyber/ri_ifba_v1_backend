# Diretrizes de Desenvolvimento - RI-IFBA Frontend

## Stack Tecnológica

- **Framework**: Vue 3 (Composition API com TypeScript)
- **UI Library**: PrimeVue 4.x (Integrado com Padrão GovBR via MCP)
- **Styling**: Tailwind CSS 4.x com tailwindcss-primeui
- **State Management**: Pinia
- **Router**: Vue Router
- **Consumo de API**: Axios (conectando ao `ri_ifba_v1_backend`)

## Ferramentas de Desenvolvimento

### Uso do MCP (Model Context Protocol)
O projeto utiliza a integração **MCP para PHPStorm**, que otimiza a geração de código seguindo rigorosamente os padrões:
1.  **Componentes PrimeVue**: Estrutura base de UI.
2.  **Padrão GovBR**: Identidade visual, acessibilidade e componentes oficiais do governo brasileiro.

---

## Convenções de Código

### 1. Estilização e Tematização

#### Use Tailwind CSS
- ❌ **NUNCA** use CSS puro ou estilos inline.
- ✅ Use classes utilitárias do Tailwind.
- ✅ Use valores relativos (rem/em) ao invés de pixels absolutos sempre que possível.

#### Cores do Tema (GovBR + Identidade IFBA)
- ❌ **NUNCA** use valores hexadecimais hardcoded nos componentes.
- ✅ Utilize as cores do **Padrão GovBR** como base, adaptando para a identidade do **IFBA**.
- ✅ Utilize os componentes do PrimeVue com o tema/preset que respeita o **Design System GovBR**.

**Paleta Principal:**
- `primary`: #32A041 (Verde IFBA - adaptado ao GovBR)
- `secondary`: #E30613 (Vermelho IFBA)
- `neutral`: Cores cinzas e fundos padronizados pelo GovBR.

### 2. Modo Escuro
- ✅ Implementar suporte nativo usando a classe `dark:` do Tailwind.
- ✅ Use sombras adaptativas (pretas para modo claro, brancas/transparentes para modo escuro).

### 3. Nomenclatura (Padrão Híbrido)
- ✅ Variáveis e funções devem ser em **Português**.
- ✅ Verbos de ação em funções no **Infinitivo** (ex: `salvarUsuario`).
- ✅ Sufixos/Prefixos técnicos do ecossistema Vue/Store podem ser em **Inglês**.

```typescript
// ❌ Evite
const isLogged = true;
function handleClose() { ... }
const userStore = useUserStore();

// ✅ Prefira
const estaLogado = true;
function fecharModalHandle() { ... } // ou apenas fecharModal
const usuarioStore = useUsuarioStore();
```

### 4. Componentização e GovBR
- ✅ Utilize preferencialmente os componentes do **PrimeVue**.
- ✅ Siga a estrutura de acessibilidade e semântica do **GovBR**.
- ✅ Máxima modularização: Componentes pequenos e com responsabilidade única.
- ✅ Pasta `components/common`: Para componentes genéricos reaproveitáveis.
- ✅ Pasta `components/[modulo]`: Para componentes específicos (ex: `components/refeicoes`, `components/justificativas`).

### 5. Estrutura de Arquivos .vue
- ✅ Ordem sugerida: `<script setup>`, `<template>`, `<style scoped>`.

```vue
<script setup lang="ts">
// Lógica com TypeScript e Composition API
</script>

<template>
  <!-- Estrutura HTML/Vue -->
</template>

<style scoped>
/* CSS residual (mínimo possível, prefira Tailwind) */
</style>
```

### 6. Integração com Backend (Regras de Negócio)
Ao desenvolver o frontend, atente-se às seguintes regras do backend:
- **Tipos de Usuário:** Diferenciar fluxos para `Bolsista`, `Estudante Regular` (Fila Extra) e `Admin`.
- **Dias de Direito:** Validar no front se o usuário tem direito à refeição no dia selecionado (baseado em `usuario_dias_semana`).
- **Justificativas:** Fluxo para upload de documentos e acompanhamento de status (Pendente/Aprovado/Rejeitado).
- **QR Code:** Tela de exibição de QR Code para o estudante e tela de leitura para o Admin.

### 7. Responsividade
- ✅ **Mobile-first**: O sistema será muito usado em celulares por estudantes na fila.
- ✅ Desktop: Foco em dashboards administrativos.

## Checklist de PR
- [ ] Código gerado via **MCP PHPStorm** validado (PrimeVue + GovBR).
- [ ] Sem estilos inline ou hexadecimais hardcoded.
- [ ] Nomenclatura em português seguida conforme padrão.
- [ ] Suporte a Dark Mode verificado.
- [ ] TypeScript sem `any`.
- [ ] Testado em resolução mobile.

---
**Última atualização**: Janeiro 2026 (Adaptado de SISRU Guidelines)
