<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useUsuarioStore } from "@/stores/usuario";
import api from "@/services/api";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";

const router = useRouter();
const usuarioStore = useUsuarioStore();

const matricula = ref("");
const senha = ref("");
const carregando = ref(false);
const erro = ref("");

async function handleLogin() {
  carregando.value = true;
  erro.value = "";

  try {
    const response = await api.post("/login", {
      matricula: matricula.value,
      password: senha.value,
    });

    const { token, user } = response.data.data;
    usuarioStore.setToken(token);
    usuarioStore.setUsuario(user);

    // Redireciona baseado no perfil
    if (user.perfil === "admin") {
      router.push("/admin");
    } else {
      router.push("/estudante");
    }
  } catch (e: unknown) {
    erro.value = "Matrícula ou senha inválidos";
    console.error(e);
  } finally {
    carregando.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200 dark:from-slate-800 dark:to-slate-900">
    <div class="bg-surface-0 dark:bg-slate-800 p-8 rounded-xl shadow-xl w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <i class="pi pi-building text-5xl text-primary-500 mb-4"></i>
        <h1 class="text-2xl font-bold text-primary-700 dark:text-white">
          RI IFBA
        </h1>
        <p class="text-surface-500 dark:text-surface-400">
          Refeitório Institucional
        </p>
      </div>

      <!-- Erro -->
      <Message v-if="erro" severity="error" class="mb-4">
        {{ erro }}
      </Message>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <label for="matricula" class="font-medium">Matrícula</label>
          <InputText
            id="matricula"
            v-model="matricula"
            placeholder="Digite sua matrícula"
            class="w-full"
            required
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="senha" class="font-medium">Senha</label>
          <Password
            id="senha"
            v-model="senha"
            placeholder="Digite sua senha"
            class="w-full"
            :feedback="false"
            toggle-mask
            required
          />
        </div>

        <Button
          type="submit"
          label="Entrar"
          icon="pi pi-sign-in"
          :loading="carregando"
          class="w-full mt-4"
        />
      </form>

      <!-- Link voltar -->
      <div class="text-center mt-6">
        <RouterLink to="/" class="text-primary-500 hover:underline">
          <i class="pi pi-arrow-left mr-2"></i>
          Voltar ao início
        </RouterLink>
      </div>
    </div>
  </div>
</template>
