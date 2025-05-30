import { fileURLToPath, URL } from 'node:url';

import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import vue from '@vitejs/plugin-vue';
import * as dotenv from 'dotenv';
import Components from 'unplugin-vue-components/vite';
import { defineConfig } from 'vite';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {

    dotenv.config({ path: `.env.${mode}` });
    console.log(`Current mode: ${mode}`);
    console.log('Env Variables:', process.env.VITE_BASE_URL || '/');

    const base = process.env.VITE_BASE_URL || '/';
    //const outDir = process.env.VITE_OUT_DIR || './';
    const outDir = './output';
    return {
        optimizeDeps: {
            noDiscovery: true
        },
        plugins: [
            vue(),
            Components({
                resolvers: [PrimeVueResolver()]
            })
        ],
        base: base,
        build: {
            outDir: outDir,
        },
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url))
            }
        }
    }
});
