import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/",
            name: "home",
            component: () => import("@/views/HomeView.vue"),
        },
        {
            path: "/login",
            name: "login",
            component: () => import("@/views/LoginView.vue"),
        },
        {
            path: "/cardapio",
            name: "cardapio",
            component: () => import("@/views/CardapioView.vue"),
        },
        // Rotas do Estudante
        {
            path: "/estudante",
            name: "estudante",
            component: () => import("@/views/estudante/DashboardView.vue"),
            meta: { requiresAuth: true, perfil: "estudante" },
            children: [
                {
                    path: "perfil",
                    name: "estudante-perfil",
                    component: () => import("@/views/estudante/PerfilView.vue"),
                },
                {
                    path: "historico",
                    name: "estudante-historico",
                    component: () => import("@/views/estudante/HistoricoView.vue"),
                },
                {
                    path: "justificativas",
                    name: "estudante-justificativas",
                    component: () => import("@/views/estudante/JustificativasView.vue"),
                },
                {
                    path: "fila-extra",
                    name: "estudante-fila",
                    component: () => import("@/views/estudante/FilaExtraView.vue"),
                },
            ],
        },
        // Rotas do Admin
        {
            path: "/admin",
            name: "admin",
            component: () => import("@/views/admin/DashboardView.vue"),
            meta: { requiresAuth: true, perfil: "admin" },
            children: [
                {
                    path: "cardapios",
                    name: "admin-cardapios",
                    component: () => import("@/views/admin/CardapiosView.vue"),
                },
                {
                    path: "bolsistas",
                    name: "admin-bolsistas",
                    component: () => import("@/views/admin/BolsistasView.vue"),
                },
                {
                    path: "presencas",
                    name: "admin-presencas",
                    component: () => import("@/views/admin/PresencasView.vue"),
                },
                {
                    path: "justificativas",
                    name: "admin-justificativas",
                    component: () => import("@/views/admin/JustificativasView.vue"),
                },
                {
                    path: "relatorios",
                    name: "admin-relatorios",
                    component: () => import("@/views/admin/RelatoriosView.vue"),
                },
                {
                    path: "usuarios",
                    name: "admin-usuarios",
                    component: () => import("@/views/admin/UsuariosView.vue"),
                },
            ],
        },
    ],
});

// Guard de autenticação
router.beforeEach((to, _from, next) => {
    const token = localStorage.getItem("token");

    if (to.meta.requiresAuth && !token) {
        next({ name: "login" });
    } else {
        next();
    }
});

export default router;
