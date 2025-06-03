<script setup>
import config from '@/config';
import { CountryService } from '@/service/CountryService';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import FloatLabel from 'primevue/floatlabel';
import Popover from 'primevue/popover';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const popovers = ref({});
const op = ref();
const toast = useToast();
const { api_post } = useApi();
const otp_data = ref();
const meal = ref([]);
const workshops = ref([]);
const prices_ticket = ref([]);
const money = ref(0);
const default_price = ref(0);
const dialogWorkshop = ref(false);
const OpenWorkshop = ref();
const price_no_pii = ref(0);
const showOtp = ref(false);
const VerifyBtnLoading = ref(false);
const isEmailInValid = ref(false);
const EmailVerified = ref(false);
const showVerify = ref(true);
const selectedDate = ref(null);
const accommodation_status = {
    female: ref(null),
    male: ref(null)
};
const datePattern = /^\d{4}-\d{2}-\d{2}$/;
const namePattern = /^[\p{L}]+(?: [\p{L}]+)*$/u;
const addressPattern = /^(?!\s+$)[\p{L}0-9\s,./-]+$/u;
const zipPattern = /^\d{3}\s?\d{2}$/;
var OpenWorkshopTitle = '';
const MoneyOnlyAccommodation = ref(0);
const locale = ref({
    firstDayOfWeek: 1, // Začátek týdne (1 = Pondělí)
    dayNames: ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'],
    dayNamesShort: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
    dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
    monthNames: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
    monthNamesShort: ['Led', 'Úno', 'Bře', 'Dub', 'Kvě', 'Čer', 'Čvc', 'Srp', 'Zář', 'Říj', 'Lis', 'Pro'],
    today: 'Dnes',
    clear: 'Vymazat'
});

const toggle = (event, field) => {
    if (popovers.value[field]) {
        popovers.value[field].toggle(event);
    }
};
const CountryselectValues = ref([]);

const handleOtpChange = (newValue) => {
    if (newValue.length === 6) {
        verify_otp();
    }
};

const static_config = ref({
    meal: true,
    only_friday: false
});

const valid = ref({
    first_name: false,
    last_name: false,
    gender: false,
    address: false,
    birthday: false,
    state: false,
    zip: false,
    program_parts: static_config.value.only_friday ? true : false,
    food: static_config.value.only_friday ? true : false,
    accommodation: true,
    food: static_config.value.only_friday ? true : static_config.value.meal ? false : true
});

const touched = ref({
    first_name: false,
    last_name: false,
    address: false,
    zip: false
});
const formVal = ref({
    email: '',
    first_name: '',
    last_name: '',
    no_pii: false,
    birthday: '',
    gender: '',
    address: '',
    state: null,
    zip: '',
    want_accommodation: false,
    fri_to_sat: false,
    sat_to_sun: false,
    part_fri: false,
    part_sat1: false,
    part_sat2: false,
    part_sat3: false,
    meal: '',
    workshop: '',
    email_notify: true
});

onMounted(() => {
    load_info();
    CountryService.getCountries().then((data) => (CountryselectValues.value = data));
});

async function verify_otp() {
    const api = await api_post(config.endpoint_login, { method: 'verify_otp', parameters: { email: formVal.value.email, otp: otp_data.value } });
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
async function load_info() {
    const api = await api_post(config.endpoint_ticket, { method: 'get_info' });
    if (config.debug) {
        console.log('API [get_info]: ');
        console.log(api);
    }
    if (api.result) {
        meal.value = Object.values(api.response.meal);
        workshops.value = Object.values(api.response.workshops);
        accommodation_status.female.value = Object.values(api.response.accormodation)[0];
        accommodation_status.male.value = Object.values(api.response.accormodation)[1];
        prices_ticket.value = api.response.prices;
        if (static_config.value.only_friday == true) {
            default_price.value = prices_ticket.value['price_ticket_only_friday'];
        } else if (static_config.value.meal == true) {
            default_price.value = prices_ticket.value['ticket_meal'];
        } else {
            default_price.value = prices_ticket.value['price_ticket_no_meal'];
        }
        money.value = default_price.value;
        price_no_pii.value = prices_ticket.value['price_ticket_no_pii'];
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

async function verify_email() {
    VerifyBtnLoading.value = true;
    const api = await api_post(config.endpoint_login, { method: 'email_verify', parameters: { email: formVal.value.email, lang: i18n.global.t('lang_code') } });
    if (config.debug) {
        console.log('API [email_verify]: ');
        console.log(api);
    }
    if (api.result) {
        email_verified();
    } else {
        VerifyBtnLoading.value = false;
        showOtp.value = true;
        isEmailInValid.value = true;
    }
}

function email_verified() {
    VerifyBtnLoading.value = false;
    isEmailInValid.value = false;
    EmailVerified.value = true;
    showOtp.value = false;
    showVerify.value = false;
    toast.add({ severity: 'success', summary: i18n.global.t('successful'), detail: i18n.global.t('verify_email_success'), life: config.toast_lifetime });
}

function accommodation_switch() {
    if (!formVal.value.want_accommodation) {
        if (formVal.value.fri_to_sat == true) {
            formVal.value.fri_to_sat = false;
            accommodation_money_update('fri_sat');
        }
        if (formVal.value.sat_to_sun == true) {
            formVal.value.sat_to_sun = false;
            accommodation_money_update('sat_sun');
        }
    }
}

function ppi_switch_change() {
    if (formVal.value.no_pii) {
        money.value = money.value + prices_ticket.value['price_ticket_no_pii'];
        valid.value.address = true;
        valid.value.zip = true;
        valid.value.first_name = true;
        valid.value.last_name = true;
        valid.value.gender = true;
        valid.value.birthday = true;
        formVal.value.address = '';
        formVal.value.zip = '';
        formVal.value.first_name = '';
        formVal.value.last_name = '';
        formVal.value.gender = '';
        gender_change();
    } else {
        money.value = money.value - prices_ticket.value['price_ticket_no_pii'];
        valid.value.address = false;
        valid.value.zip = false;
        valid.value.first_name = false;
        valid.value.last_name = false;
        valid.value.birthday = false;
        touched.address = false;
        touched.zip = false;
        touched.first_name = false;
        touched.last_name = false;
        valid.value.gender = false;
    }
    toast.add({ severity: 'info', summary: i18n.global.t('price_update_title'), detail: i18n.global.t('price_update_text') + money.value + ' ' + i18n.global.t('currency_shortcut'), life: config.toast_lifetime });
}

const handleBlur = (field) => {
    touched.value[field] = true;
};

function accommodation_money_update(type) {
    if (type == 'fri_sat') {
        if (formVal.value.fri_to_sat) {
            money.value = money.value + prices_ticket.value['price_one_night'];
            MoneyOnlyAccommodation.value += prices_ticket.value['price_one_night'];
        } else {
            money.value = money.value - prices_ticket.value['price_one_night'];
            MoneyOnlyAccommodation.value -= prices_ticket.value['price_one_night'];
        }
    } else if (type == 'sat_sun') {
        if (formVal.value.sat_to_sun) {
            money.value = money.value + prices_ticket.value['price_one_night'];
            MoneyOnlyAccommodation.value += prices_ticket.value['price_one_night'];
        } else {
            money.value = money.value - prices_ticket.value['price_one_night'];
            MoneyOnlyAccommodation.value -= prices_ticket.value['price_one_night'];
        }
    }

    toast.add({ severity: 'info', summary: i18n.global.t('price_update_title'), detail: i18n.global.t('price_update_text') + money.value + ' ' + i18n.global.t('currency_shortcut'), life: config.toast_lifetime });
}

function dialogOpen(workshop) {
    OpenWorkshop.value = workshop;
    OpenWorkshopTitle = workshop.title;
    dialogWorkshop.value = true;
}

const isFormValid = computed(() => {
    return Object.values(valid.value).every((value) => value);
});

function check_selected_parts() {
    if (static_config.value.only_friday == true) {
        return true;
    }
    if (formVal.value.part_sat1 || formVal.value.part_sat2 || formVal.value.part_sat3) {
        return true;
    }
    return false;
}

function check_selected_food() {
    if (static_config.value.only_friday == true || static_config.value.meal == false) {
        return true;
    }
    /* check if all is dissabled */
    if (formVal.value.meal) {
        return true;
    }
    return false;
}

function check_selected_accommodation() {
    if (formVal.value.want_accommodation) {
        if (formVal.value.fri_to_sat || formVal.value.sat_to_sun) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

function check_state() {
    if (formVal.value.state == '' || formVal.value.state == null) {
        return false;
    } else {
        return true;
    }
}

function check_gender() {
    if (formVal.value.gender == 'male' || formVal.value.gender == 'female') {
        return true;
    } else {
        return false;
    }
}

watch(
    formVal,
    (newVal) => {
        valid.value.first_name = !formVal.value.no_pii ? namePattern.test(newVal.first_name.trim()) : true;
        valid.value.last_name = !formVal.value.no_pii ? newVal.last_name.replace(/\s+/g, '') !== '' : true;
        valid.value.gender = !formVal.value.no_pii ? check_gender() : true;
        valid.value.address = !formVal.value.no_pii ? addressPattern.test(newVal.address.trim()) : true;
        valid.value.birthday = !formVal.value.no_pii ? datePattern.test(newVal.birthday.trim()) : true;
        valid.value.state = !formVal.value.no_pii ? check_state() : true;
        valid.value.zip = !formVal.value.no_pii ? zipPattern.test(newVal.zip.trim()) : true;
        valid.value.program_parts = check_selected_parts();
        valid.value.food = check_selected_food();
        valid.value.accommodation = check_selected_accommodation();
    },
    { deep: true }
);

watch(selectedDate, (newValue) => {
    if (newValue) {
        const adjustedDate = new Date(newValue);
        adjustedDate.setDate(adjustedDate.getDate() + 1);
        formVal.value.birthday = adjustedDate.toISOString().split('T')[0];
    } else {
        formVal.value.birthday = null;
    }
});

async function get_ticket() {
    const api = await api_post(config.endpoint_ticket, { method: 'get_ticket', parameters: { form: formVal.value, static_cfg: static_config.value, price: money.value } });
    if (config.debug) {
        console.log('API [get_ticket]: ');
        console.log(api);
    }
    if (api.result) {
        router.push(api.response);
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response), life: config.toast_lifetime });
    }
}
const toggle1 = (event) => {
    if (!isFormValid.value) {
        op.value.toggle(event);
        get_ticket();
    } else {
        get_ticket();
    }
};

function gender_change() {
    formVal.value.want_accommodation = false;
    accommodation_switch();
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
                <div v-if="!static_config.only_friday && static_config.meal" class="card flex flex-col gap-4 w-full">
                    <Fieldset :legend="$t('intro_page_pricing_only_allmeal_title')" :toggleable="true">
                        <p class="m-0 multiline-text">
                            {{ $t('ticket_intro_text_with_food') }}
                        </p>
                    </Fieldset>
                </div>
                <div v-else-if="!static_config.only_friday && !static_config.meal" class="card flex flex-col gap-4 w-full">
                    <Fieldset :legend="$t('intro_page_pricing_only_all_title')" :toggleable="true">
                        <p class="m-0 multiline-text">
                            {{ $t('ticket_intro_text_without_food') }}
                        </p>
                    </Fieldset>
                </div>
                <div v-else class="card flex flex-col gap-4 w-full">
                    <Fieldset :legend="$t('intro_page_pricing_only_friday_title')" :toggleable="true">
                        <p class="m-0 multiline-text">
                            {{ $t('ticket_intro_text_only_friday') }}
                        </p>
                    </Fieldset>
                </div>
            </div>
            <div class="card flex flex-col gap-4 mt-4">
                <div class="formgrid grid">
                    <div class="field col-12 md:col-6">
                        <FloatLabel variant="on" class="w-full">
                            <label for="email2">*{{ $t('sign_in_email') }}</label>
                            <InputText v-model="formVal.email" :invalid="isEmailInValid" type="text" />
                        </FloatLabel>
                    </div>
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
                                <Button :loading="VerifyBtnLoading" @click="verify_email">{{ $t('verify') }}</Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="EmailVerified">
                <div class="flex mt-8">
                    <ToggleSwitch v-model="formVal.no_pii" @change="ppi_switch_change"></ToggleSwitch><label style="margin-left: 10px">{{ $t('ticket_register_pii_switch') }}</label>
                </div>
                <div v-if="!formVal.no_pii" class="flex mt-8">
                    <div class="card flex flex-col gap-4 w-full">
                        <div class="font-semibold text-xl">{{ $t('personal_information') }}</div>
                        <div class="flex flex-col md:flex-row gap-8">
                            <div id="ticket_first_name" class="flex flex-wrap gap-2 w-full">
                                <FloatLabel variant="on" class="w-full">
                                    <label for="first_name">*{{ $t('first_name') }}</label>
                                    <InputText v-model="formVal.first_name" id="first_name" @click="toggle($event, 'first_name')" type="text" @blur="handleBlur('first_name')" :invalid="touched.first_name && !valid.first_name" />
                                </FloatLabel>
                                <Popover v-if="touched.first_name && !valid.first_name" :ref="(el) => (popovers.first_name = el)" class="ticket-font-popup">
                                    <div class="flex flex-col gap-4">{{ $t('ticket_invalid_text_first_name') }}</div>
                                </Popover>
                            </div>
                            <div id="ticket_last_name" class="flex flex-wrap gap-2 w-full">
                                <FloatLabel variant="on" class="w-full">
                                    <label for="lastname2">*{{ $t('last_name') }}</label>
                                    <InputText v-model="formVal.last_name" @click="toggle($event, 'last_name')" name="last_name" id="lastname2" type="text" @blur="handleBlur('last_name')" :invalid="touched.last_name && !valid.last_name" />
                                </FloatLabel>
                                <Popover v-if="touched.last_name && !valid.last_name" :ref="(el) => (popovers.last_name = el)" class="ticket-font-popup">
                                    <div class="flex flex-col gap-4">{{ $t('ticket_invalid_text_last_name') }}</div>
                                </Popover>
                            </div>
                            <div id="ticket_birthday" class="flex-wrap gap-2 w-full">
                                <FloatLabel variant="on" class="w-full">
                                    <label for="birthdate">*{{ $t('date_of_birth') }}</label>
                                    <DatePicker :showIcon="true" :showButtonBar="true" v-model="selectedDate" @blur="handleBlur('birthday')" :invalid="touched.birthday && !valid.birthday" dateFormat="yy-mm-dd" :locale="locale"></DatePicker>
                                </FloatLabel>
                            </div>
                        </div>
                        <div id="ticket_gender" class="flex flex-wrap gap-2 mt-4 w-full my-center">
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile">
                                        <input v-model="formVal.gender" type="radio" name="gender" value="male" class="tile-checkbox" @change="gender_change" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-solid fa-person"></i></div>
                                            <span>{{ $t('male') }}</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile">
                                        <input v-model="formVal.gender" type="radio" name="gender" value="female" class="tile-checkbox" @change="gender_change" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-solid fa-person-dress"></i></div>
                                            <span>{{ $t('female') }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="ticket_address" class="flex flex-wrap gap-2 mt-4 w-full">
                            <FloatLabel variant="on" class="w-full">
                                <label for="address">*{{ $t('address') }}</label>
                                <Textarea v-model="formVal.address" @click="toggle($event, 'address')" id="address" rows="1" @blur="handleBlur('address')" :invalid="touched.address && !valid.address" />
                            </FloatLabel>
                            <Popover v-if="touched.address && !valid.address" :ref="(el) => (popovers.address = el)" class="ticket-font-popup">
                                <div class="flex flex-col gap-4">{{ $t('ticket_invalid_text_address') }}</div>
                            </Popover>
                        </div>

                        <div id="ticket_state" class="flex flex-col md:flex-row mt-2 gap-4">
                            <div class="flex flex-wrap gap-2 w-full">
                                <Select id="state" v-model="formVal.state" :options="CountryselectValues" optionLabel="name" optionValue="name" :placeholder="$t('select_country')" class="w-full" :filter="true">
                                    <template #option="slotProps">
                                        <div class="flex items-center">
                                            <span :class="'mr-2 flag flag-' + slotProps.option.code.toLowerCase()" style="width: 18px; height: 12px" />
                                            <div>{{ slotProps.option.name }}</div>
                                        </div>
                                    </template>
                                </Select>
                            </div>
                            <div id="ticket_zip" class="flex flex-wrap md:flex-row gap-2 w-full">
                                <FloatLabel variant="on" class="w-full">
                                    <label for="zip">*{{ $t('zip') }}</label>
                                    <InputMask v-model="formVal.zip" @click="toggle($event, 'zip')" id="zip" mask="999 99" placeholder="000 00" @blur="handleBlur('zip')" :invalid="touched.zip && !valid.zip" />
                                </FloatLabel>
                                <Popover v-if="touched.zip && !valid.zip" :ref="(el) => (popovers.zip = el)" class="ticket-font-popup">
                                    <div class="flex flex-col gap-4">{{ $t('ticket_invalid_text_last_name') }}</div>
                                </Popover>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex mt-8">
                    <div class="card flex flex-col gap-4 w-full">{{ $t('ticket_register_no_pii_text') }} ({{ price_no_pii }} {{ $t('currency_shortcut') }})</div>
                </div>

                <div v-if="!static_config.only_friday">
                    <div class="flex mt-8">
                        <ToggleSwitch v-model="formVal.want_accommodation" @change="accommodation_switch" :disabled="formVal.gender == 'male' || formVal.gender == 'female' ? false : true" /><label style="margin-left: 10px">{{
                            $t('accommodation_switch')
                        }}</label>
                        <p v-if="formVal.gender == 'male' || formVal.gender == 'female' ? false : true" class="pl-4 text-primary-500">({{ $t('acommodation_switch_gender_select') }})</p>
                    </div>
                    <div id="ticket_accommodation" v-if="formVal.want_accommodation" class="flex mt-8">
                        <div class="card flex flex-col gap-4 w-full">
                            <div class="font-semibold text-xl">{{ $t('accommodation') }}</div>
                            <p class="multiline-text">{{ $t('accommodation_tictet_text') }} {{ prices_ticket['price_one_night'] }} {{ $t('currency_shortcut') }}</p>
                            <br />
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile" :class="{ 'tile-disable': accommodation_status[formVal.gender].value.friday_saturday.disable }">
                                        <input v-model="formVal.fri_to_sat" type="checkbox" class="tile-checkbox" @change="accommodation_money_update('fri_sat')" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-regular fa-calendar-days"></i></div>
                                            <span class="mb-4">{{ $t('accommodation_fri_sat') }}</span>
                                            <Message v-if="accommodation_status[formVal.gender].value.friday_saturday.warning_show" severity="warn">
                                                {{ $t('accommodation_warning_message', { count: accommodation_status[formVal.gender].value.friday_saturday.waring_threshold }) }}</Message
                                            >
                                            <Message class="meal-run-out-message" v-if="accommodation_status[formVal.gender].value.friday_saturday.disable" severity="error">{{ $t('unavailable') }}</Message>
                                            <Message
                                                class="meal-run-out-message"
                                                v-if="!accommodation_status[formVal.gender].value.friday_saturday.disable && !accommodation_status[formVal.gender].value.friday_saturday.warning_show"
                                                severity="success"
                                                >{{ $t('available') }}</Message
                                            >
                                        </div>
                                    </label>
                                </div>
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile" :class="{ 'tile-disable': accommodation_status[formVal.gender].value.saturday_sunday.disable }">
                                        <input v-model="formVal.sat_to_sun" type="checkbox" class="tile-checkbox" @change="accommodation_money_update('sat_sun')" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-regular fa-calendar-days"></i></div>
                                            <span class="mb-4">{{ $t('accommodation_sat_sun') }}</span>
                                            <Message v-if="accommodation_status[formVal.gender].value.saturday_sunday.warning_show" severity="warn">
                                                {{ $t('accommodation_warning_message', { count: accommodation_status[formVal.gender].value.saturday_sunday.waring_threshold }) }}</Message
                                            >
                                            <Message class="meal-run-out-message" v-if="accommodation_status[formVal.gender].value.saturday_sunday.disable" severity="error">{{ $t('unavailable') }}</Message>
                                            <Message
                                                class="meal-run-out-message"
                                                v-if="!accommodation_status[formVal.gender].value.saturday_sunday.disable && !accommodation_status[formVal.gender].value.saturday_sunday.warning_show"
                                                severity="success"
                                                >{{ $t('available') }}</Message
                                            >
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!static_config.only_friday" class="flex mt-8">
                    <div id="ticket_program" class="card flex flex-col gap-4 w-full">
                        <div class="font-semibold text-xl">{{ $t('program_parts') }}</div>
                        <p>{{ $t('ticket_part_program_text') }}</p>
                        <p v-if="!valid.program_parts" class="text-red-500">{{ $t('ticket_part_program_red_text') }}</p>
                        <div class="flex flex-col md:flex-row gap-8">
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="formVal.part_fri" type="checkbox" class="tile-checkbox" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-solid fa-p"></i></div>
                                        <span>{{ $t('friday') }}</span>
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="formVal.part_sat1" type="checkbox" class="tile-checkbox" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-solid fa-sun"></i></div>
                                        <span>{{ $t('saturday_morning') }}</span>
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="formVal.part_sat2" type="checkbox" class="tile-checkbox" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-solid fa-s"></i></div>
                                        <span>{{ $t('saturday_afternoon') }}</span>
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="formVal.part_sat3" type="checkbox" class="tile-checkbox" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-solid fa-moon"></i></div>
                                        <span>{{ $t('saturday_evening') }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!static_config.only_friday && static_config.meal" class="flex mt-8">
                    <div id="ticket_food" class="card flex flex-col gap-4 w-full">
                        <div class="font-semibold text-xl">{{ $t('lunch') }}</div>
                        <p>{{ $t('ticket_lunch_text') }}</p>
                        <div class="flex flex-col md:flex-row gap-8 my-center">
                            <div v-for="(item, index) in meal" :key="index">
                                <div class="flex gap-2 w-full my-center max-w-[1000px]">
                                    <label class="tile" :class="{ 'tile-disable': item.disable }">
                                        <input v-if="!item.disable" v-model="formVal.meal" type="radio" name="meal" :value="item.id" class="tile-checkbox" />
                                        <div class="tile-content" style="width: 250px">
                                            <span class="tile-content-title">{{ $t(item.title) }}</span>
                                            <div class="image-container"><img :src="`/demo/images/lunch/${item.img}`" /></div>
                                            <div class="flex mb-4 mt-4">
                                                <div v-for="(alerg, j) in item.alerg" :key="j">
                                                    <Badge class="ml-2" :value="alerg"></Badge>
                                                </div>
                                            </div>
                                            <Message v-if="item.warning_show" severity="warn"> {{ $t('lunch_warning_message', { count: item.waring_threshold }) }}</Message>
                                            <Message class="meal-run-out-message" v-if="item.disable" severity="error">{{ $t('unavailable') }}</Message>
                                            <Message class="meal-run-out-message" v-if="!item.disable && !item.warning_show" severity="success">{{ $t('available') }}</Message>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!static_config.only_friday" class="flex mt-8 mb-4">
                    <div class="card flex flex-col gap-4 w-full">
                        <div class="font-semibold text-xl">{{ $t('intro_page_features_workshops_title') }}</div>
                        <p>{{ $t('ticket_workshop_test') }}</p>
                        <div class="flex flex-wrap gap-8 max-w-[1000px] mx-auto my-center">
                            <div v-for="(item, index) in workshops" :key="index">
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile" :class="{ 'tile-disable': item.disable }">
                                        <input v-if="!item.disable" v-model="formVal.workshop" type="radio" name="workshop" :value="item.id" class="tile-checkbox" />
                                        <div class="tile-content" style="width: 250px">
                                            <span class="tile-content-title">{{ $t(item.title) }}</span>
                                            <div class="image-container mb-4"><img :src="`/demo/images/workshops/${item.img}`" /></div>
                                            <Button class="mb-4" @click="dialogOpen(item)">{{ $t('more_info') }}</Button>
                                            <Message v-if="item.warning_show" severity="warn"> {{ $t('accommodation_warning_message', { count: item.waring_threshold }) }}</Message>
                                            <Message class="meal-run-out-message" v-if="item.disable" severity="error">{{ $t('unavailable') }}</Message>
                                            <Message class="meal-run-out-message" v-if="!item.disable && !item.warning_show" severity="success">{{ $t('available') }}</Message>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center mb-4 mt-4">
                    <Checkbox id="checkOption1" v-model="formVal.email_notify" binary />
                    <label for="checkOption1" class="ml-2">{{ $t('check_want_be_notified') }}</label>
                </div>
            </div>
        </Fluid>
        <div v-if="EmailVerified">
            <hr />
            <p>
                <b>{{ $t('summary') }}:</b><br />
            </p>
            <table>
                <tr>
                    <td class="pr-6">{{ $t('price_text_selected_package') }}</td>
                    <td>{{ default_price }} {{ $t('currency_shortcut') }}</td>
                </tr>
                <tr v-if="formVal.no_pii">
                    <td class="pr-6">{{ $t('summary_no_pii_text') }}</td>
                    <td>+ {{ price_no_pii }} {{ $t('currency_shortcut') }}</td>
                </tr>
                <tr v-if="formVal.want_accommodation">
                    <td class="pr-6">{{ $t('accommodation') }}</td>
                    <td>+ {{ MoneyOnlyAccommodation }} {{ $t('currency_shortcut') }}</td>
                </tr>
            </table>
            <div class="my-right">
                <p>
                    {{ $t('total_price') }}: <br /><span class="font-semibold" style="font-size: 50px">{{ money }}</span>
                    <span class="font-semibold" style="font-size: 25px"> {{ $t('currency_shortcut') }}</span>
                </p>
            </div>
            <br />
            <div class="my-right"><Button :label="$t('ticket_get_ticket')" style="width: 150px" @click="toggle1"></Button></div>

            <Popover ref="op">
                <div class="flex flex-col gap-4">
                    <div>
                        <span class="font-medium block mb-2"
                            ><b>{{ $t('ticket_something_is_missing') }}</b></span
                        >
                        <hr />
                        <ul v-if="!formVal.no_pii" class="list-none p-0 m-0 flex flex-col">
                            <a href="#ticket_first_name" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.first_name, 'fa-regular fa-circle-xmark text-red-500': !valid.first_name }"></i></span>
                                <span class="font-semibold">{{ $t('first_name') }}</span>
                            </a>
                            <a href="#ticket_last_name" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.last_name, 'fa-regular fa-circle-xmark text-red-500': !valid.last_name }"></i></span>
                                <span class="font-semibold">{{ $t('last_name') }}</span>
                            </a>
                            <a href="#ticket_birthday" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.birthday, 'fa-regular fa-circle-xmark text-red-500': !valid.birthday }"></i></span>
                                <span class="font-semibold">{{ $t('date_of_birth') }}</span>
                            </a>
                            <a href="#ticket_gender" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.gender, 'fa-regular fa-circle-xmark text-red-500': !valid.gender }"></i></span>
                                <span class="font-semibold">{{ $t('gender') }}</span>
                            </a>
                            <a href="#ticket_address" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.address, 'fa-regular fa-circle-xmark text-red-500': !valid.address }"></i></span>
                                <span class="font-semibold">{{ $t('address') }}</span>
                            </a>
                            <a href="#ticket_state" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.state, 'fa-regular fa-circle-xmark text-red-500': !valid.state }"></i></span>
                                <span class="font-semibold">{{ $t('state') }}</span>
                            </a>
                            <a href="#ticket_zip" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.zip, 'fa-regular fa-circle-xmark text-red-500': !valid.zip }"></i></span>
                                <span class="font-semibold">{{ $t('zip') }}</span>
                            </a>
                        </ul>
                        <ul v-if="formVal.want_accommodation" class="list-none p-0 m-0 flex flex-col">
                            <a href="#ticket_accommodation" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.accommodation, 'fa-regular fa-circle-xmark text-red-500': !valid.accommodation }"></i></span>
                                <span class="font-semibold">{{ $t('accommodation') }}</span>
                            </a>
                        </ul>
                        <ul v-if="!static_config.only_friday" class="list-none p-0 m-0 flex flex-col">
                            <a href="#ticket_program" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.program_parts, 'fa-regular fa-circle-xmark text-red-500': !valid.program_parts }"></i></span>
                                <span class="font-semibold">{{ $t('program_parts') }}</span>
                            </a>
                        </ul>
                        <ul v-if="static_config.meal && !static_config.only_friday" class="list-none p-0 m-0 flex flex-col">
                            <a href="#ticket_food" class="flex items-center gap-2 px-2 py-3 hover:bg-emphasis cursor-pointer rounded-border">
                                <span class="text-sm"><i :class="{ 'fa-regular fa-circle-check text-green-500 ': valid.food, 'fa-regular fa-circle-xmark text-red-500': !valid.food }"></i></span>
                                <span class="font-semibold">{{ $t('lunch') }}</span>
                            </a>
                        </ul>
                    </div>
                </div>
            </Popover>

            <br />
        </div>
    </div>
    <Dialog :header="$t(OpenWorkshopTitle)" v-model:visible="dialogWorkshop" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <img class="mb-4" :src="`/demo/images/workshops/${OpenWorkshop.img}`" />
        <p class="leading-normal m-0">
            {{ $t(OpenWorkshop.desc) }}
        </p>
        <template #footer> </template>
    </Dialog>
    <Toast />
</template>
<style>
.ticket-font-popup {
    text-align: center;
    max-width: 90%;
    font-size: 12px;
    color: #d85d5d;
}
</style>
