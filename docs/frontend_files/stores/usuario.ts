import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type { Usuario } from "@/types/usuario";

export const useUsuarioStore = defineStore("usuario", () => {
    // State
    const usuario = ref<Usuario | null>(null);
    const token = ref<string | null>(localStorage.getItem("token"));
    const carregando = ref(false);

    // Getters
    const estaLogado = computed(() => !!token.value);
    const ehBolsista = computed(() => usuario.value?.bolsista ?? false);
    const ehAdmin = computed(() => usuario.value?.perfil === "admin");
    const nomeUsuario = computed(() => usuario.value?.nome ?? "");

    // Actions
    function setUsuario(novoUsuario: Usuario) {
        usuario.value = novoUsuario;
    }

    function setToken(novoToken: string) {
        token.value = novoToken;
        localStorage.setItem("token", novoToken);
    }

    function logout() {
        usuario.value = null;
        token.value = null;
        localStorage.removeItem("token");
    }

    return {
        // State
        usuario,
        token,
        carregando,
        // Getters
        estaLogado,
        ehBolsista,
        ehAdmin,
        nomeUsuario,
        // Actions
        setUsuario,
        setToken,
        logout,
    };
});
