<script setup lang="ts">
import { RouterView, RouterLink } from "vue-router";
import Button from "primevue/button";
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-primary-600 text-white p-4 flex flex-col">
      <div class="mb-8">
        <h1 class="text-xl font-bold">RI IFBA</h1>
        <p class="text-sm text-primary-200">Área do Estudante</p>
      </div>

      <nav class="flex-1 space-y-2">
        <RouterLink to="/estudante" class="block p-3 rounded hover:bg-primary-500 transition">
          <i class="pi pi-home mr-2"></i> Dashboard
        </RouterLink>
        <RouterLink to="/estudante/perfil" class="block p-3 rounded hover:bg-primary-500 transition">
          <i class="pi pi-user mr-2"></i> Meu Perfil
        </RouterLink>
        <RouterLink to="/estudante/historico" class="block p-3 rounded hover:bg-primary-500 transition">
          <i class="pi pi-history mr-2"></i> Histórico
        </RouterLink>
        <RouterLink to="/estudante/justificativas" class="block p-3 rounded hover:bg-primary-500 transition">
          <i class="pi pi-file mr-2"></i> Justificativas
        </RouterLink>
        <RouterLink to="/estudante/fila-extra" class="block p-3 rounded hover:bg-primary-500 transition">
          <i class="pi pi-list mr-2"></i> Fila Extra
        </RouterLink>
      </nav>

      <div class="mt-auto">
        <Button label="Sair" icon="pi pi-sign-out" severity="secondary" class="w-full" />
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 bg-surface-50 dark:bg-slate-900 p-6">
      <RouterView />
    </main>
  </div>
</template>
