<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import api from "@/services/api";
import type { Cardapio } from "@/types";
import Card from "primevue/card";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import ProgressSpinner from "primevue/progressspinner";

const cardapios = ref<Cardapio[]>([]);
const carregando = ref(true);

const diasSemana = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];

const semanaAtual = computed(() => {
  const hoje = new Date();
  const domingo = new Date(hoje);
  domingo.setDate(hoje.getDate() - hoje.getDay());
  const sabado = new Date(domingo);
  sabado.setDate(domingo.getDate() + 6);
  
  return `${domingo.toLocaleDateString("pt-BR")} - ${sabado.toLocaleDateString("pt-BR")}`;
});

onMounted(async () => {
  try {
    const response = await api.get("/cardapio/semanal");
    cardapios.value = response.data.data;
  } catch (e) {
    console.error("Erro ao carregar cardápio:", e);
  } finally {
    carregando.value = false;
  }
});

function getNomeDia(data: string): string {
  const date = new Date(data + "T12:00:00");
  return diasSemana[date.getDay()];
}
</script>

<template>
  <div class="min-h-screen bg-surface-50 dark:bg-slate-900">
    <!-- Header -->
    <header class="bg-primary-500 text-white p-4">
      <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-bold">
          <i class="pi pi-calendar mr-2"></i>
          Cardápio Semanal
        </h1>
        <RouterLink to="/">
          <Button icon="pi pi-home" label="Início" text class="text-white" />
        </RouterLink>
      </div>
    </header>

    <!-- Content -->
    <main class="container mx-auto p-4">
      <Card class="mb-4">
        <template #title>
          Semana: {{ semanaAtual }}
        </template>
      </Card>

      <!-- Loading -->
      <div v-if="carregando" class="flex justify-center p-8">
        <ProgressSpinner />
      </div>

      <!-- Cardápios -->
      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card v-for="cardapio in cardapios" :key="cardapio.id" class="shadow-md">
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-calendar text-primary-500"></i>
              {{ getNomeDia(cardapio.dataDoCardapio) }}
            </div>
          </template>
          <template #subtitle>
            {{ new Date(cardapio.dataDoCardapio).toLocaleDateString("pt-BR") }}
          </template>
          <template #content>
            <ul class="space-y-2 text-sm">
              <li><strong>Proteína 1:</strong> {{ cardapio.pratoPrincipalPtn01 }}</li>
              <li><strong>Proteína 2:</strong> {{ cardapio.pratoPrincipalPtn02 }}</li>
              <li v-if="cardapio.guarnicao"><strong>Guarnição:</strong> {{ cardapio.guarnicao }}</li>
              <li><strong>Acomp. 1:</strong> {{ cardapio.acompanhamento01 }}</li>
              <li><strong>Acomp. 2:</strong> {{ cardapio.acompanhamento02 }}</li>
              <li v-if="cardapio.salada"><strong>Salada:</strong> {{ cardapio.salada }}</li>
              <li v-if="cardapio.ovoLactoVegetariano">
                <strong class="text-green-600">🥬 Vegetariano:</strong> {{ cardapio.ovoLactoVegetariano }}
              </li>
              <li v-if="cardapio.suco"><strong>Suco:</strong> {{ cardapio.suco }}</li>
              <li v-if="cardapio.sobremesa"><strong>Sobremesa:</strong> {{ cardapio.sobremesa }}</li>
            </ul>
          </template>
        </Card>
      </div>

      <!-- Empty State -->
      <Card v-if="!carregando && cardapios.length === 0" class="text-center">
        <template #content>
          <i class="pi pi-calendar-times text-4xl text-surface-400 mb-4"></i>
          <p class="text-surface-500">Nenhum cardápio disponível para esta semana.</p>
        </template>
      </Card>
    </main>
  </div>
</template>
