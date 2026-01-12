import { createApp } from "vue";
import { createPinia } from "pinia";
import PrimeVue from "primevue/config";
import Aura from "@primevue/themes/aura";
import router from "./router";
import App from "./App.vue";

// PrimeIcons
import "primeicons/primeicons.css";

// Styles
import "./style.css";

// Definição do tema com paleta verde IFBA
const temaIfba = {
    preset: Aura,
    options: {
        prefix: "p",
        darkModeSelector: ".dark",
        cssLayer: false,
    },
    semantic: {
        primary: {
            50: "#E7F4E9",
            100: "#BBDABD",
            200: "#95B199",
            300: "#749A77",
            400: "#548557",
            500: "#2F9E41",
            600: "#21752E",
            700: "#134F1D",
            800: "#072C0C",
            900: "#021504",
        },
    },
};

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(PrimeVue, { theme: temaIfba });

app.mount("#app");
