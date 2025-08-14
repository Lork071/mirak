<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const popovers = ref({});
const op = ref();
const toast = useToast();
const { api_post } = useApi();
const email = ref('');
const first_name = ref('');
const last_name = ref('');
const phone_number = ref('');
const phone_prefix = ref('+420'); // výchozí předvolba
const email_verified = ref(false);
const volunteer_note = ref('');
const btnLoading = ref(false);

function setEmail(val) {
    email.value = val;
}
function setEmailVerified(val) {
    email_verified.value = val;
}

const phone_number_full = computed(() => {
    // Odstraní případné mezery na začátku/konci a spojí předvolbu s číslem
    return `${phone_prefix.value} ${phone_number.value.trim()}`;
});

// Validace: všechna pole musí být vyplněná a telefon začínat +
const canSend = computed(() => first_name.value.trim().length > 0 && last_name.value.trim().length > 0 && /^\+\d{9,}$/.test(phone_number_full.value.replace(/\s/g, '')));

async function send_volunteer() {
    btnLoading.value = true;
    const api = await api_post(config.endpoint_volunteer, {
        method: 'send_volunteer',
        parameters: {
            email: email.value,
            first_name: first_name.value,
            last_name: last_name.value,
            phone_number: phone_number_full.value,
            volunteer_note: volunteer_note.value,
            lang: i18n.global.t('lang_code')
        }
    });
    if (config.debug) {
        console.log('API [send_volunteer]: ');
        console.log(api);
    }
    if (api.result) {
        btnLoading.value = false;
        router.push('/mirak-crew/success');
    } else {
        btnLoading.value = false;
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response.desc), life: config.toast_lifetime });
    }
}
</script>

<template>
    <header class="intro-page-navbar">
        <div class="intro-page-navbar-container">
            <!-- Logo a Title -->
            <div class="intro-page-navbar-left">
                <svg width="60" height="54" id="mirak_logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600">
                    <rect style="fill: var(--primary-color)" class="cls-1" x="190.92" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-115.96 167.22) rotate(-30)" />
                    <rect style="fill: var(--primary-color)" class="cls-1" x="373.29" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-91.53 258.4) rotate(-30)" />
                    <circle style="fill: var(--primary-color)" class="cls-1" cx="117.98" cy="378.56" r="62.54" />
                </svg>
                <span class="intro-page-navbar-left-title">{{ $t('name_event') }}</span>
            </div>
            <div class="intro-page-navbar-lang my-right">
                <LanguageConfigurator />
            </div>
        </div>
    </header>

    <div class="intro-page-wraper">
        <Fluid>
            <div class="flex mt-8">
                <Fieldset :legend="$t('mirak_crew')" :toggleable="true">
                    <p class="m-0 multiline-text">
                        {{ $t('volunteer_text') }}
                    </p>
                </Fieldset>
            </div>
            <div class="card flex flex-col gap-4 mt-4">
                <EmailVerify @email="setEmail" @verified="setEmailVerified" />
            </div>
            <div v-if="email_verified">
                <div class="card flex flex-col gap-4 w-full">
                    <div class="font-semibold text-xl">{{ $t('personal_information') }}</div>
                    <div class="flex flex-col md:flex-row gap-8">
                        <div id="volunteer_first_name" class="flex flex-wrap gap-2 w-full">
                            <FloatLabel variant="on" class="w-full">
                                <label for="first_name">*{{ $t('first_name') }}</label>
                                <InputText v-model="first_name" id="first_name" type="text" />
                            </FloatLabel>
                        </div>
                        <div id="volunteer_last_name" class="flex flex-wrap gap-2 w-full">
                            <FloatLabel variant="on" class="w-full">
                                <label for="lastname2">*{{ $t('last_name') }}</label>
                                <InputText v-model="last_name" id="lastname2" type="text" />
                            </FloatLabel>
                        </div>
                        <div id="volunteer_phone_number" class="flex flex-wrap gap-2 w-full">
                            <FloatLabel variant="on" class="w-full">
                                <label for="phone_number">*{{ $t('phone_number') }} ({{ $t('phone_number_extensions') }})</label>
                                <div class="flex gap-2 w-full">
                                    <Dropdown
                                        v-model="phone_prefix"
                                        :options="[
                                            { label: '+420', value: '+420', code: 'CZ' },
                                            { label: '+421', value: '+421', code: 'SK' },
                                            { label: '+48', value: '+48', code: 'PL' },
                                            { label: '+49', value: '+49', code: 'DE' },
                                            { label: '+43', value: '+43', code: 'AT' },
                                            { label: '+44', value: '+44', code: 'GB' }
                                        ]"
                                        optionLabel="label"
                                        optionValue="value"
                                        style="width: 150px"
                                    >
                                        <template #option="slotProps">
                                            <span :class="'mr-2 flag flag-' + slotProps.option.code.toLowerCase()" style="width: 18px; height: 12px" />
                                            {{ slotProps.option.label }}
                                        </template>
                                    </Dropdown>
                                    <InputMask id="basic" v-model="phone_number" mask="999 999 999" placeholder="123 456 789" style="flex: 1" type="number" />
                                </div>
                            </FloatLabel>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 w-full mt-4">
                        <FloatLabel variant="on" class="w-full">
                            <label for="volunteer_note">{{ $t('volunteer_note') }}</label>
                            <Textarea id="volunteer_note" v-model="volunteer_note" :maxlength="2500" style="width: 100%" :rows="3" />
                        </FloatLabel>
                        <span class="text-xs text-muted-color">{{ volunteer_note.length }}/2500</span>
                    </div>
                    <div class="my-right">
                        <Button :label="$t('send')" style="width: 150px" @click="send_volunteer" :disabled="!canSend || btnLoading" :loading="btnLoading"></Button>
                    </div>
                </div>
            </div>
        </Fluid>
    </div>

    <Toast />
</template>
