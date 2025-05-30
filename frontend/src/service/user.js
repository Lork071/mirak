// src/services/ApiService.js
import config from '@/config';
import { useApi } from '@/service/api';
import { defineStore } from 'pinia';
import { useRouter } from 'vue-router';

export const useUserStore = defineStore('user', {
    state: () => ({
        userData: null // here the user data will be saved
    }),
    actions: {
        async fetchUserData(path) {
            const { api_post } = useApi();
            const router = useRouter();
            const response = await api_post(config.endpoint_login, { method: 'get_user_info', parameters: { page_path: path } });
            if (config.debug) console.log(response);
            if (!response.result) {
                router.push(response.response);
            } else {
                this.userData = response.response;
            }
        },
        page_premission(label) {
            return this.userData.user_info.premissions_pages.includes(label);
        }
    }
});
