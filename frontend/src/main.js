// Force dark mode for both PrimeVue and Tailwind
document.documentElement.classList.add('app-dark', 'dark');
import '@/assets/styles.scss';
import '@/assets/tailwind.css';
import '@/assets/zdark-overrides.css';

import i18n from '@/service/i18n.js';
import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import { createApp } from 'vue';
import { VueQrcodeReader } from 'vue-qrcode-reader';
import AnimateOnScroll from 'vue3-animate-onscroll';
import App from './App.vue';
import router from './router';
const MirakPresent = definePreset(Aura, {
    options: {
        darkModeSelector: '.app-dark'
    },
    semantic: {
        // tvoje fialová – nechávám beze změny, případně si doladíš
        primary: {
            50: '#faf5ff',
            100: '#f3e8ff',
            200: '#e9d5ff',
            300: '#d8b4fe',
            400: '#c084fc',
            500: '#a855f7',
            600: '#9333ea',
            700: '#7e22ce',
            800: '#6b21a8',
            900: '#581c87',
            950: '#3b0764'
        },

        // KLÍČ: tmavé "surface" pro celé UI (karty, inputy, panely…)
        surface: {
            0: '#0c1020', // hlavní pozadí stránky
            50: '#11162a', // základ pro karty/panely
            100: '#161c33',
            200: '#1c2340',
            300: '#212a4c',
            400: '#263158',
            500: '#2b3864',
            600: '#313f70',
            700: '#38477d',
            800: '#3f4f8a',
            900: '#465798',
            950: '#4d60a6'
        }
    }
});

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: MirakPresent
    }
});
app.use(ToastService);
app.use(ConfirmationService);
app.use(i18n);
app.use(VueQrcodeReader);
app.use(AnimateOnScroll);
app.mount('#app');
