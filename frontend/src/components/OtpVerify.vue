<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import FloatLabel from 'primevue/floatlabel';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

const toast = useToast();
const { api_post } = useApi();
const showOtp = ref(false);
const VerifyBtnLoading = ref(false);
const isEmailInValid = ref(false);
const EmailVerified = ref(false);
const showVerify = ref(true);
const otp_data = ref();
const emit = defineEmits(['verified', 'email']);

const email = ref('');

async function verify_otp() {
    const api = await api_post(config.endpoint_login, { method: 'verify_otp', parameters: { email: email.value, otp: otp_data.value } });
    if (config.debug) {
        console.log('API [verify_otp]: ');
        console.log(api);
    }
    if (api.result) {
        email_verified();
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response), life: config.toast_lifetime });
    }
}

async function generate_otp() {
    VerifyBtnLoading.value = true;
    const api = await api_post(config.endpoint_login, { method: 'generate_otp', parameters: { email: email.value, lang: i18n.global.t('lang_code') } });
    if (config.debug) {
        console.log('API [generate_otp]: ');
        console.log(api);
    }
    if (api.result) {
        VerifyBtnLoading.value = false;
        showOtp.value = true;
        isEmailInValid.value = true;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response), life: config.toast_lifetime });
        VerifyBtnLoading.value = false;
    }
}

function email_verified() {
    VerifyBtnLoading.value = false;
    isEmailInValid.value = false;
    showOtp.value = false;
    showVerify.value = false;
    EmailVerified.value = true;
    emit('verified', EmailVerified.value);
    emit('email', email.value);
    toast.add({ severity: 'success', summary: i18n.global.t('successful'), detail: i18n.global.t('otp_verify_success'), life: config.toast_lifetime });
}

const handleOtpChange = (newValue) => {
    if (newValue.length === 6) {
        verify_otp();
    }
};
</script>

<template>
    <Fluid>
        <FloatLabel variant="on" class="w-full">
            <label for="email2">*{{ $t('sign_in_email') }}</label>

            <InputText v-model="email" :invalid="isEmailInValid" type="text" :disabled="EmailVerified" />
        </FloatLabel>
        <div v-if="showVerify">
            <div v-if="showOtp" class="flex flex-col items-center mt-4">
                <div class="mt-4 mb-4">{{ $t('verify_email_text_email_send') }}</div>
                <InputOtp class="mt-2 mb-2" v-model="otp_data" size="small" :length="6" @update:modelValue="handleOtpChange" />
                <div class="mt-4 mb-4">{{ $t('verify_email_resend_otp_text') }}</div>
                <div class="my-right">
                    <Button @click="verify_email" :loading="VerifyBtnLoading" text>{{ $t('verify_email_resend_otp_button') }}</Button>
                </div>
            </div>
            <div v-if="!showOtp" class="my-right">
                <div class="mt-4" style="width: 150px">
                    <Button :loading="VerifyBtnLoading" @click="generate_otp">{{ $t('send_otp') }}</Button>
                </div>
            </div>
        </div>
    </Fluid>
</template>
