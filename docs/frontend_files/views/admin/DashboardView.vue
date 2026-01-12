<script setup lang="ts">
import { RouterView, RouterLink } from "vue-router";
import Button from "primevue/button";
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Sidebar Admin -->
    <aside class="w-64 bg-primary-700 text-white p-4 flex flex-col">
      <div class="mb-8">
        <h1 class="text-xl font-bold">RI IFBA</h1>
        <p class="text-sm text-primary-300">Administração</p>
      </div>

      <nav class="flex-1 space-y-2">
        <RouterLink to="/admin" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-chart-bar mr-2"></i> Dashboard
        </RouterLink>
        <RouterLink to="/admin/cardapios" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-calendar mr-2"></i> Cardápios
        </RouterLink>
        <RouterLink to="/admin/bolsistas" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-users mr-2"></i> Bolsistas
        </RouterLink>
        <RouterLink to="/admin/presencas" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-check-circle mr-2"></i> Presenças
        </RouterLink>
        <RouterLink to="/admin/justificativas" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-file mr-2"></i> Justificativas
        </RouterLink>
        <RouterLink to="/admin/relatorios" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-file-pdf mr-2"></i> Relatórios
        </RouterLink>
        <RouterLink to="/admin/usuarios" class="block p-3 rounded hover:bg-primary-600 transition">
          <i class="pi pi-user-edit mr-2"></i> Usuários
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
