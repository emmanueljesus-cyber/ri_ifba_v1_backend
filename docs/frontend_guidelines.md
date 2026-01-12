# Diretrizes de Desenvolvimento - RI IFBA Frontend

## Stack Tecnológica

| Tecnologia | Versão/Detalhes |
|------------|-----------------|
| Framework | Vue 3 (Composition API + TypeScript) |
| UI Library | PrimeVue 4.5.4 (Aura theme) |
| Styling | Tailwind CSS 4.1.18 + tailwindcss-primeui |
| State | Pinia |
| Router | Vue Router |

---

## Convenções de Código

### 1. Estilização
- ❌ NUNCA use CSS puro ou estilos inline
- ✅ Use classes utilitárias do Tailwind
- ✅ Use valores relativos ao invés de pixels absolutos

### 2. Cores do Tema PrimeVue
- ❌ NUNCA use valores hexadecimais hardcoded
- ✅ Use variáveis de tema do PrimeVue

| Cor | Uso |
|-----|-----|
| `primary` | #2C3E6B (azul principal) |
| `primary-100` | tons claros |
| `primary-600` | tons escuros |
| `surface-0` | fundo branco/claro |
| `surface-100` | fundo cinza claro |
| `surface-700` | texto escuro |

### 3. Modo Escuro
```html
<div class="bg-surface-0 dark:bg-slate-900 text-primary dark:text-white"></div>
<div class="shadow-[2px_0_4px_rgba(0,0,0,0.1)] dark:shadow-[2px_0_8px_rgba(255,255,255,0.1)]"></div>
```

### 4. Componentização
- Crie componentes pequenos e reutilizáveis
- Use composables quando apropriado
- Prefira componentes PrimeVue antes de criar do zero
- Componentes reutilizáveis em `components/common/`

### 5. Assets
- ❌ NUNCA use placeholders SVG inline
- ✅ Use imagens dos assets: `@/assets/img/logo.png`

### 6. State Management (Pinia)
```typescript
import { useSidebarStore } from "@/stores/sidebar";
import { storeToRefs } from "pinia";

const sidebarStore = useSidebarStore();
const { menuItems } = storeToRefs(sidebarStore);
```

### 7. Nomenclatura (PORTUGUÊS)
```typescript
// ❌ Evite
handleCloseRegisterRestaurant, useUserStore, isOpenSideBar

// ✅ Prefira  
handleFecharCadastro, useUsuarioStore, estaAbertaSidebar
```

### 8. Estrutura .vue
```vue
<script setup lang="ts">
// 1. Script primeiro
</script>

<template>
  <!-- 2. Template depois -->
</template>

<style scoped>
/* 3. Style por último (se necessário) */
</style>
```

### 9. TypeScript
```typescript
interface Props {
  estaAberto?: boolean;
}

interface Emits {
  (e: "fechar"): void;
}
```

### 10. Responsividade
- Mobile-first approach
- Desktop: `md:` prefix (≥768px)

---

## Estrutura de Pastas

```
src/
├── assets/
│   └── img/
├── components/
│   ├── common/
│   └── sideBar/
├── composables/
├── config/
├── router/
├── stores/
├── types/
└── views/
```

---

## Figma
- Link: https://www.figma.com/design/OBu35XIaBVCEWvLCFhH7kT/RI-IFBA

---

## Checklist de PR
- [ ] Usa classes Tailwind (não CSS inline)
- [ ] Usa cores do tema PrimeVue
- [ ] Funciona em modo claro e escuro
- [ ] Responsivo (mobile e desktop)
- [ ] Assets corretos (não placeholders)
- [ ] TypeScript sem erros
- [ ] Segue estrutura modular
- [ ] Consultou Figma
