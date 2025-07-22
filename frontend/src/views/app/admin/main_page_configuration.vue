<template>
    <div class="card p-4 space-y-6">
        <h2 class="text-2xl font-bold mb-6">{{ $t('main_page_configuration') }}</h2>
        <div v-if="configuration" class="flex flex-col gap-6">
            <template v-for="(value, key) in configuration" :key="key">
                <!-- Pokud je hodnota "true" nebo "false", zobraz switch -->
                <div v-if="value === true || value === false" class="flex items-center gap-4">
                    <ToggleSwitch v-model="configuration[key]" :true-value="true" :false-value="false" />
                    <span class="font-medium">{{ $t(key) }}</span>
                </div>
                <!-- Pokud je hodnota jiný text, zobraz textarea -->
                <div v-else class="flex flex-col gap-2">
                    <label class="font-medium">{{ $t(key) }}</label>
                    <Textarea v-model="configuration[key]" rows="3" class="w-full" />
                </div>
            </template>
        </div>
        <div class="flex justify-end mt-8">
            <Button :label="i18n.global.t('save')" icon="pi pi-save" @click="saveConfig" :disabled="!configuration" severity="primary" />
        </div>
    </div>
</template>

<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

const router = useRoute();
const toast = useToast();
const { api_post } = useApi();
const configuration = ref(null);

async function load_static_cfg() {
    const api = await api_post(config.endpoint_static, {
        method: 'get_intro_cfg',
        parameters: {}
    });
    if (config.debug) {
        console.log('API [get_intro_cfg]: ');
        console.log(api);
    }
    if (api.result) {
        configuration.value = api.response;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response.desc), life: config.toast_lifetime });
    }
}

async function saveConfig() {
    const api = await api_post(config.endpoint_static, {
        method: 'update_cfg',
        parameters: { static_config: configuration.value }
    });
    if (config.debug) {
        console.log('API [update_cfg]: ');
        console.log(api);
    }
    if (api.result) {
        toast.add({ severity: 'success', summary: i18n.global.t('success'), detail: i18n.global.t('configuration_saved'), life: config.toast_lifetime });
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_api'), life: config.toast_lifetime });
    }
}

onMounted(() => {
    load_static_cfg();
});
</script>
