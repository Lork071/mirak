// src/services/ApiService.js
import i18n from '@/service/i18n';
import { useUserStore } from '@/service/user';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import config from '../config';

export function useApi() {
    const api = axios.create({
        baseURL: config.apiBaseUrl,
        headers: {
            'Content-Type': 'application/json'
        },
        withCredentials: true
    });
    const toast = useToast();

    const api_get = async (endpoint) => {
        const response = {
            result: false,
            response: 'NA'
        };
        try {
            response = api.get(endpoint);
        } catch (error) {
            if (config.debug) toast.add({ severity: 'info', summary: 'Debug', detail: error, life: config.toast_lifetime });
            toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_api'), life: config.toast_lifetime });
        }

        return response;
    };

    const api_post = async (endpoint, data) => {
        let response = {
            result: false,
            response: 'NA'
        };
        const user_storage = useUserStore();
        if (user_storage.userData !== null) {
            data['user_info'] = user_storage.userData.user_info;
        }

        try {
            response = await api.post(endpoint, JSON.stringify(data));
            if (config.debug) {
                console.log('Response from [api_post]:');
                console.log(response);
            }
            response = response.data;
        } catch (error) {
            if (config.debug) toast.add({ severity: 'info', summary: 'Debug', detail: error, life: config.toast_lifetime });
            toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_api'), life: config.toast_lifetime });
        }

        return response;
    };

    return { api_get, api_post };
}
