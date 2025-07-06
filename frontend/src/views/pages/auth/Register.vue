<script setup>
import EmailVerify from '@/components/EmailVerify.vue';
import LanguageConfigurator from '@/components/LanguageConfigurator.vue';
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const checked = ref(false);

const email_verified = ref(false);

const first_name = ref('');
const last_name = ref('');

const toast = useToast();
const { api_post } = useApi();
const router = useRouter();

function setEmail(val) {
    email.value = val;
}
function setEmailVerified(val) {
    email_verified.value = val;
}

const passwordValid = computed(() => ({
    length: password.value.length >= 8,
    number: /\d/.test(password.value),
    lowercase: /[a-z]/.test(password.value),
    uppercase: /[A-Z]/.test(password.value)
}));

async function sign_up() {
    const response = await api_post(config.endpoint_login, { method: 'sign_up', parameters: { email: email.value, password: password.value, first_name: first_name.value, last_name: last_name.value } });
    if (response.result) {
        toast.add({ severity: 'success', summary: i18n.global.t(response.response.title), detail: i18n.global.t(response.response.desc), life: config.toast_lifetime });
        await new Promise((resolve) => setTimeout(resolve, 2000));
        router.push('/app');
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(response.response.title), detail: i18n.global.t(response.response.desc), life: config.toast_lifetime });
    }
}

const isPasswordStrong = computed(() => passwordValid.value.length && passwordValid.value.number && passwordValid.value.lowercase && passwordValid.value.uppercase);

// Validace: jméno a příjmení musí mít aspoň 2 znaky a obsahovat jen písmena
const firstNameValid = computed(() => /^[A-Za-zÁ-Žá-žěščřžýáíéúůóďťň ]{2,}$/.test(first_name.value));
const lastNameValid = computed(() => /^[A-Za-zÁ-Žá-žěščřžýáíéúůóďťň ]{2,}$/.test(last_name.value));

// Celková validita formuláře
const canRegister = computed(() => isPasswordStrong.value && firstNameValid.value && lastNameValid.value);
</script>

<template>
    <div class="fixed flex gap-4 top-8 right-8">
        <LanguageConfigurator />
    </div>

    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20 sm:w-[500px]" style="border-radius: 53px">
                    <div class="text-center mb-8">
                        <svg
                            width="100%"
                            height="54"
                            viewBox="0 0 14.2875 14.2875"
                            version="1.1"
                            id="svg1"
                            xml:space="preserve"
                            sodipodi:docname="Logo.svg"
                            inkscape:version="1.4 (86a8ad7, 2024-10-11)"
                            inkscape:export-filename="Logo1.svg"
                            inkscape:export-xdpi="96"
                            inkscape:export-ydpi="96"
                            xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
                            xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlns:svg="http://www.w3.org/2000/svg"
                        >
                            <defs id="defs1" />
                            <g inkscape:label="Layer 1" inkscape:groupmode="layer" id="layer1" transform="translate(76.27383,-67.512647)">
                                <path
                                    style="fill: var(--primary-color)"
                                    d="m -74.039133,78.065249 c -0.280795,-0.02117 -0.559603,-0.113775 -0.792729,-0.263292 -0.251874,-0.161542 -0.448942,-0.377923 -0.580737,-0.637649 -0.07963,-0.156925 -0.125617,-0.300021 -0.154188,-0.479779 -0.01632,-0.102667 -0.0196,-0.329177 -0.0062,-0.43057 0.08901,-0.675265 0.585965,-1.216545 1.264714,-1.37753 0.144352,-0.03424 0.230835,-0.04336 0.408507,-0.04307 0.166432,2.65e-4 0.244117,0.0078 0.376594,0.03644 0.51476,0.11135 0.957852,0.467588 1.16696,0.938216 0.08197,0.184486 0.124078,0.368656 0.132898,0.58127 0.01573,0.379111 -0.103606,0.744514 -0.341355,1.045252 -0.06307,0.07979 -0.197321,0.212732 -0.278724,0.276025 -0.2385,0.185441 -0.53346,0.307661 -0.832971,0.34515 -0.09476,0.01186 -0.270627,0.01649 -0.36274,0.0095 z m 4.816373,2.6e-4 c -0.29142,-0.02207 -0.568199,-0.113786 -0.808985,-0.268062 -0.193566,-0.124021 -0.379525,-0.309333 -0.499555,-0.497817 -0.06953,-0.10918 -2.44337,-4.076053 -2.471655,-4.130325 -0.123835,-0.237611 -0.183956,-0.511429 -0.173314,-0.789346 0.01336,-0.348852 0.126336,-0.657509 0.343119,-0.9374 0.06468,-0.08351 0.234462,-0.245828 0.325348,-0.311046 0.249318,-0.178902 0.512685,-0.280543 0.826072,-0.318806 0.09294,-0.01135 0.326719,-0.0097 0.424464,0.003 0.262563,0.03415 0.507135,0.122032 0.72127,0.259172 0.180717,0.115738 0.365526,0.295618 0.48017,0.467366 0.04621,0.06923 2.457768,4.097134 2.489494,4.158081 0.109575,0.210494 0.165871,0.427307 0.174885,0.673535 0.01478,0.403616 -0.114983,0.784092 -0.372351,1.091804 -0.0656,0.07844 -0.21085,0.214429 -0.291308,0.272749 -0.201938,0.146374 -0.435995,0.249556 -0.67243,0.296437 -0.146188,0.02899 -0.35021,0.0416 -0.495224,0.03062 z m 4.890962,0.0023 c -0.256773,-0.01709 -0.479884,-0.0763 -0.695738,-0.184627 -0.240613,-0.120755 -0.455526,-0.305082 -0.612309,-0.525167 -0.03235,-0.04541 -2.41982,-4.023802 -2.493459,-4.155005 -0.08505,-0.151532 -0.148324,-0.340642 -0.179456,-0.536336 -0.01537,-0.09659 -0.01525,-0.353545 2.09e-4,-0.453976 0.05812,-0.377599 0.231798,-0.70453 0.509598,-0.95925 0.130271,-0.119448 0.260029,-0.206336 0.423311,-0.283453 0.210858,-0.09959 0.401142,-0.148095 0.641382,-0.163505 0.599661,-0.03846 1.173288,0.231278 1.512306,0.711144 0.06445,0.09123 2.472107,4.112935 2.52244,4.21344 0.07292,0.145614 0.121007,0.298478 0.146078,0.464401 0.09486,0.627796 -0.188769,1.247354 -0.730754,1.596266 -0.227895,0.146711 -0.457291,0.230232 -0.725029,0.263975 -0.07536,0.0095 -0.255072,0.01632 -0.318579,0.01209 z"
                                    id="path1"
                                />
                            </g>
                        </svg>
                        <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">{{ $t('welcome_in_mirak_account') }}</div>
                        <span class="text-muted-color font-medium">{{ $t('register_text') }}</span>
                    </div>

                    <Fluid class="flex flex-col">
                        <EmailVerify @email="setEmail" @verified="setEmailVerified" />
                        <div v-if="email_verified" class="flex flex-col gap-4">
                            <FloatLabel variant="on" class="w-full mt-8">
                                <label for="first_name">{{ $t('first_name') }}</label>
                                <InputText v-model="first_name" type="text" />
                            </FloatLabel>
                            <FloatLabel variant="on" class="w-full mt-4">
                                <label for="last_name">{{ $t('last_name') }}</label>
                                <InputText v-model="last_name" type="text" />
                            </FloatLabel>
                            <FloatLabel variant="on" class="w-full mt-4">
                                <label for="password">{{ $t('sign_in_password') }}</label>
                                <InputText v-model="password" type="password" />
                            </FloatLabel>

                            <ul class="password-checklist mt-2 mb-2 text-sm">
                                <li :class="{ ok: passwordValid.length }">
                                    <span v-if="passwordValid.length"><i class="fa-solid fa-check success"></i></span>
                                    <span v-else><i class="fa-solid fa-xmark danger" style="color: var(--primary-color)"></i></span>
                                    {{ $t('password_min_length', { min: 8 }) }}
                                </li>
                                <li :class="{ ok: passwordValid.number }">
                                    <span v-if="passwordValid.number"><i class="fa-solid fa-check success"></i></span>
                                    <span v-else><i class="fa-solid fa-xmark" style="color: var(--primary-color)"></i></span>
                                    {{ $t('password_number') }}
                                </li>
                                <li :class="{ ok: passwordValid.lowercase }">
                                    <span v-if="passwordValid.lowercase"><i class="fa-solid fa-check success"></i></span>
                                    <span v-else><i class="fa-solid fa-xmark" style="color: var(--primary-color)"></i></span>
                                    {{ $t('password_lowercase') }}
                                </li>
                                <li :class="{ ok: passwordValid.uppercase }">
                                    <span v-if="passwordValid.uppercase"><i class="fa-solid fa-check success"></i></span>
                                    <span v-else><i class="fa-solid fa-xmark" style="color: var(--primary-color)"></i></span>
                                    {{ $t('password_uppercase') }}
                                </li>
                            </ul>
                            <div v-if="!showOtp" class="my-right">
                                <div class="mt-4" style="width: 150px">
                                    <Button :loading="VerifyBtnLoading" @click="sign_up" :disabled="!canRegister">{{ $t('sign_up') }}</Button>
                                </div>
                            </div>
                        </div>
                    </Fluid>
                </div>
            </div>
        </div>
    </div>
    <Toast />
</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
