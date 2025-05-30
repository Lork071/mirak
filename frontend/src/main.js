import i18n from '@/service/i18n.js';
import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import { createApp } from 'vue';
import { VueQrcodeReader } from 'vue-qrcode-reader';
import App from './App.vue';
import router from './router';

import '@/assets/styles.scss';
import '@/assets/tailwind.css';

const MirakPresent = definePreset(Aura, {
    options: {
        darkModeSelector: '.app-dark'
    },
    semantic: {
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
app.mount('#app');
