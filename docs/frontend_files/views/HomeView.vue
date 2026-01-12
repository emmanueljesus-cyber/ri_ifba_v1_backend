<script setup lang="ts">
import { RouterLink } from "vue-router";
import Button from "primevue/button";
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-primary-500 text-white p-4">
      <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-bold">
          <i class="pi pi-building mr-2"></i>
          Refeitório Institucional IFBA
        </h1>
        <nav class="flex gap-4">
          <RouterLink to="/cardapio">
            <Button label="Cardápio" icon="pi pi-calendar" text class="text-white" />
          </RouterLink>
          <RouterLink to="/login">
            <Button label="Entrar" icon="pi pi-sign-in" />
          </RouterLink>
        </nav>
      </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200 dark:from-slate-800 dark:to-slate-900">
      <div class="text-center p-8">
        <h2 class="text-4xl font-bold text-primary-700 dark:text-white mb-4">
          Bem-vindo ao RI IFBA
        </h2>
        <p class="text-lg text-surface-600 dark:text-surface-300 mb-8 max-w-md mx-auto">
          Sistema de gerenciamento do Refeitório Institucional do IFBA.
          Consulte cardápios, gerencie presenças e muito mais.
        </p>
        <div class="flex gap-4 justify-center">
          <RouterLink to="/cardapio">
            <Button label="Ver Cardápio" icon="pi pi-calendar" size="large" />
          </RouterLink>
          <RouterLink to="/login">
            <Button label="Fazer Login" icon="pi pi-user" size="large" severity="secondary" />
          </RouterLink>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-surface-800 text-white p-4 text-center">
      <p>© 2026 IFBA - Instituto Federal da Bahia</p>
    </footer>
  </div>
</template>
