<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import { CountryService } from '@/service/CountryService';
import i18n from '@/service/i18n';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import QrcodeVue from 'qrcode.vue';
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const role_not_pay = ref('organizer');
const router = useRoute();
const toast = useToast();
const { api_post } = useApi();
const participant = ref([]);
const participant_default = ref([]);
const id_ticket = ref();
const CountryselectValues = ref([]);
const meal = ref([]);
const workshops = ref();
const dialogWorkshop = ref(false);
const OpenWorkshop = ref();
const prices_ticket = ref();
const isChanged = ref(false);

const registration_value = ref('');
const registration_options = ref(['participant_registred', 'participant_not_registred']);

const food_value = ref('');
const food_options = ref(['delivered', 'not_delivered']);

const level = ref('M');
const renderAs = ref('svg');
const gradient = ref(true);
const gradientStartColor = ref('var(--primary-color-800)');
const gradientEndColor = ref('#000');
const margin = ref(1);
const gradientType = ref('radius');

const fast_data_update = ref([]);

const qrSize = 300;
const logoSize = 60;

const accommodation = {
    female: ref(null),
    male: ref(null)
};
var OpenWorkshopTitle = '';
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

async function read_one_participant() {
    const api = await api_post(config.endpoint_ticket, { method: 'read_one_participant', parameters: { id: id_ticket.value } });
    if (config.debug) {
        console.log('API [read_one_participant]: ');
        console.log(api);
    }
    if (api.result) {
        meal.value = api.response.food;
        workshops.value = api.response.workshops;
        accommodation.female.value = Object.values(api.response.accommodation)[0];
        accommodation.male.value = Object.values(api.response.accommodation)[1];
        prices_ticket.value = api.response.prices;
        participant.value = api.response.participant;
        participant.value.part_fri = Boolean(Number(participant.value.part_fri));
        participant.value.part_sat1 = Boolean(Number(participant.value.part_sat1));
        participant.value.part_sat2 = Boolean(Number(participant.value.part_sat2));
        participant.value.part_sat3 = Boolean(Number(participant.value.part_sat3));
        participant.value.email_notify = Boolean(Number(participant.value.email_notify));
        participant.value.food_delivered = Boolean(Number(participant.value.food_delivered));
        participant.value.fri_to_sat = Boolean(Number(participant.value.fri_to_sat));
        participant.value.sat_to_sun = Boolean(Number(participant.value.sat_to_sun));
        participant.value.want_accommodation = Boolean(Number(participant.value.want_accommodation));
        participant.value.no_pii = Boolean(Number(participant.value.no_pii));
        participant.value.register = Boolean(Number(participant.value.register));
        if (participant.value.register) {
            registration_value.value = 'participant_registred';
        } else {
            registration_value.value = 'participant_not_registred';
        }
        if (participant.value.food_delivered) {
            food_value.value = 'delivered';
        } else {
            food_value.value = 'not_delivered';
        }
        participant_default.value = JSON.stringify(participant.value);
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

async function fast_update_participant(ref, field) {
    fast_data_update.value = {
        field: field,
        value: ref.value.value == 'delivered' || ref.value.value == 'participant_registred' ? 1 : 0
    };
    const api = await api_post(config.endpoint_ticket, { method: 'fast_update_participant', parameters: { id: id_ticket.value, update_data: fast_data_update.value } });
    if (config.debug) {
        console.log('API [read_one_participant]: ');
        console.log(api);
    }
    if (api.result) {
        read_one_participant();
        toast.add({ severity: 'success', summary: i18n.global.t('success'), detail: i18n.global.t(api.response), life: config.toast_lifetime });
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

async function price_recal() {
    const api = await api_post(config.endpoint_ticket, { method: 'check_money_items', parameters: participant.value });
    if (config.debug) {
        console.log('API [check_money_items]: ');
        console.log(api);
    }
    if (api.result) {
        participant.value.pay = api.money;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

watch(
    participant,
    (newVal) => {
        isChanged.value = JSON.stringify(participant.value) !== participant_default.value;
        price_recal();
    },
    { deep: true }
);

onMounted(() => {
    id_ticket.value = router.query.id;
    read_one_participant();
    CountryService.getCountries().then((data) => (CountryselectValues.value = data));
});
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4 ws-full">
            <div class="font-semibold text-xl mb-4">
                <i v-if="participant.role == role_not_pay" class="fa-solid fa-star fa-beat mr-2" style="color: #e01515"></i>{{ participant.email
                }}<i v-if="participant.role == role_not_pay" class="fa-solid fa-star fa-beat ml-2" style="color: #e01515"></i>
            </div>
            <div class="flex">
                <label class="font-semibold mr-4">{{ $t('user_id') }}:</label>{{ participant.id }}
            </div>
            <Tabs value="0">
                <TabList>
                    <Tab value="0">{{ $t('registration') }}</Tab>
                    <Tab value="1">{{ $t('meal') }}</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="0">
                        <label
                            ><b>{{ $t('state_of_registration') }} :</b></label
                        ><br />
                        <SelectButton class="mt-2" v-model="registration_value" :options="registration_options" @change="(val) => fast_update_participant({ value: val }, 'register')">
                            <template #option="slotProps">
                                {{ $t(slotProps.option) }}
                            </template>
                        </SelectButton>
                        <div v-if="participant.register" class="mt-2">
                            <label class="mr-2"
                                ><b>{{ $t('performed') }}</b
                                >:</label
                            >
                            <label>{{ participant.register_person }}</label
                            ><br />
                            <label class="mr-2"
                                ><b>{{ $t('time') }}</b
                                >:</label
                            >
                            <label>{{ participant.register_time }}</label>
                        </div>
                    </TabPanel>
                    <TabPanel value="1">
                        <label
                            ><b>{{ $t('participant_lunch_status_text') }} :</b></label
                        ><br />
                        <SelectButton class="mt-2" v-model="food_value" :options="food_options" @change="(val) => fast_update_participant({ value: val }, 'food_delivered')">
                            <template #option="slotProps">
                                {{ $t(slotProps.option) }}
                            </template>
                        </SelectButton>
                        <div v-if="participant.food_delivered" class="mt-2">
                            <label class="mr-2"
                                ><b>{{ $t('performed') }}</b
                                >:</label
                            >
                            <label>{{ participant.food_delivered_person }}</label
                            ><br />
                            <label class="mr-2"
                                ><b>{{ $t('time') }}</b
                                >:</label
                            >
                            <label>{{ participant.food_delivered_time }}</label>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
            <Divider></Divider>
            <div v-if="participant.role != 'organizer'" class="flex flex-col md:flex-row gap-8">
                <div id="ticket_first_name" class="flex flex-wrap gap-2 w-full">
                    <label for="first_name">{{ $t('total_price') }}</label>
                    <InputGroup>
                        <InputGroupAddon>{{ $t('currency_shortcut') }}</InputGroupAddon>
                        <InputNumber v-model="participant.pay" placeholder="Price" />
                        <InputGroupAddon>/{{ participant.pay }}</InputGroupAddon>
                    </InputGroup>
                </div>
            </div>
            <Accordion>
                <AccordionPanel value="0">
                    <AccordionHeader>{{ $t('personal_information') }}</AccordionHeader>
                    <AccordionContent>
                        <div class="mb-4">
                            <ToggleSwitch v-model="participant.no_pii" @change="gender_change"></ToggleSwitch><label style="margin-left: 10px">{{ $t('ticket_register_pii_switch') }}</label>
                        </div>
                        <div v-if="!participant.no_pii">
                            <div class="flex flex-col md:flex-row gap-8">
                                <div id="ticket_first_name" class="flex flex-wrap gap-2 w-full">
                                    <label for="first_name">{{ $t('first_name') }}</label>
                                    <InputText v-model="participant.first_name" id="first_name" type="text" />
                                </div>
                                <div id="ticket_last_name" class="flex flex-wrap gap-2 w-full">
                                    <label for="lastname2">{{ $t('last_name') }}</label>
                                    <InputText v-model="participant.last_name" name="last_name" id="lastname2" />
                                </div>
                                <div id="ticket_birthday" class="flex-wrap gap-2 w-full">
                                    <label for="birthdate">{{ $t('date_of_birth') }}</label>
                                    <DatePicker
                                        :showIcon="true"
                                        :showButtonBar="true"
                                        v-model="participant.birthday"
                                        dateFormat="yy-mm-dd"
                                        :modelValue="participant.birthday ? new Date(participant.birthday) : null"
                                        @update:modelValue="(val) => (participant.birthday = val instanceof Date ? val.toISOString().slice(0, 10) : '')"
                                    />
                                </div>
                            </div>
                            <div id="ticket_gender" class="flex flex-wrap gap-2 mt-4 w-full my-center">
                                <div class="flex flex-col md:flex-row gap-4">
                                    <div class="flex gap-2 w-full my-center">
                                        <label class="tile">
                                            <input v-model="participant.gender" type="radio" name="gender" value="male" class="tile-checkbox" />
                                            <div class="tile-content">
                                                <div class="tile-content-icon"><i class="fa-solid fa-person"></i></div>
                                                <span>{{ $t('male') }}</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="flex gap-2 w-full my-center">
                                        <label class="tile">
                                            <input v-model="participant.gender" type="radio" name="gender" value="female" class="tile-checkbox" />
                                            <div class="tile-content">
                                                <div class="tile-content-icon"><i class="fa-solid fa-person-dress"></i></div>
                                                <span>{{ $t('female') }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="ticket_address" class="flex flex-wrap gap-2 mt-4 w-full">
                                <label for="address">{{ $t('address') }}</label>
                                <Textarea v-model="participant.address" id="address" rows="1" />
                            </div>

                            <div id="ticket_state" class="flex flex-col md:flex-row mt-2 gap-4">
                                <div class="flex flex-wrap gap-2 w-full">
                                    <label for="zip">{{ $t('state') }}</label>
                                    <Select id="state" v-model="participant.state" :options="CountryselectValues" optionLabel="name" optionValue="name" :placeholder="$t('select_country')" class="w-full" :filter="true">
                                        <template #option="slotProps">
                                            <div class="flex items-center">
                                                <span :class="'mr-2 flag flag-' + slotProps.option.code.toLowerCase()" style="width: 18px; height: 12px" />
                                                <div>{{ slotProps.option.name }}</div>
                                            </div>
                                        </template>
                                    </Select>
                                </div>
                                <div id="ticket_zip" class="flex flex-wrap md:flex-row gap-2 w-full">
                                    <label for="zip">{{ $t('zip') }}</label>
                                    <InputMask v-model="participant.zip" id="zip" mask="999 99" placeholder="000 00" />
                                </div>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="1">
                    <AccordionHeader>{{ $t('role') }}</AccordionHeader>
                    <AccordionContent>
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="participant.role" type="radio" name="role" value="participant" class="tile-checkbox" @change="role_change" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-solid fa-user"></i></div>
                                        <span>{{ $t('participant') }}</span>
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="participant.role" type="radio" name="role" value="organizer" class="tile-checkbox" @change="role_change" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-solid fa-star"></i></div>
                                        <span>{{ $t('organizer') }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="2">
                    <AccordionHeader>{{ $t('accommodation') }}</AccordionHeader>
                    <AccordionContent>
                        <div class="flex mt-8">
                            <ToggleSwitch v-model="participant.want_accommodation" @change="recal_price" :disabled="participant.gender == 'male' || participant.gender == 'female' ? false : true" /><label style="margin-left: 10px">{{
                                $t('accommodation_switch')
                            }}</label>
                            <p v-if="participant.gender == 'male' || participant.gender == 'female' ? false : true" class="pl-4 text-primary-500">({{ $t('acommodation_switch_gender_select') }})</p>
                        </div>
                        <div v-if="participant.want_accommodation" class="flex flex-col md:flex-row gap-4">
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="participant.fri_to_sat" type="checkbox" class="tile-checkbox" @change="recal_price" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-regular fa-calendar-days"></i></div>
                                        <span class="mb-4">{{ $t('accommodation_fri_sat') }}</span>
                                        <Message v-if="accommodation[participant.gender].value.friday_saturday.warning_show" severity="warn"
                                            >{{ accommodation[participant.gender].value.friday_saturday.booked }} / {{ accommodation[participant.gender].value.friday_saturday.max_count }}</Message
                                        >
                                        <Message class="meal-run-out-message" v-if="accommodation[participant.gender].value.friday_saturday.disable" severity="error"
                                            >{{ accommodation[participant.gender].value.friday_saturday.booked }} / {{ accommodation[participant.gender].value.friday_saturday.max_count }}</Message
                                        >
                                        <Message class="meal-run-out-message" v-if="!accommodation[participant.gender].value.friday_saturday.disable && !accommodation[participant.gender].value.friday_saturday.warning_show" severity="success"
                                            >{{ accommodation[participant.gender].value.friday_saturday.booked }} / {{ accommodation[participant.gender].value.friday_saturday.max_count }}</Message
                                        >
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-2 w-full my-center">
                                <label class="tile">
                                    <input v-model="participant.sat_to_sun" type="checkbox" class="tile-checkbox" @change="recal_price" />
                                    <div class="tile-content">
                                        <div class="tile-content-icon"><i class="fa-regular fa-calendar-days"></i></div>
                                        <span class="mb-4">{{ $t('accommodation_sat_sun') }}</span>
                                        <Message v-if="accommodation[participant.gender].value.saturday_sunday.warning_show" severity="warn"
                                            >{{ accommodation[participant.gender].value.saturday_sunday.booked }} / {{ accommodation[participant.gender].value.saturday_sunday.max_count }}</Message
                                        >
                                        <Message class="meal-run-out-message" v-if="accommodation[participant.gender].value.saturday_sunday.disable" severity="error"
                                            >{{ accommodation[participant.gender].value.saturday_sunday.booked }} / {{ accommodation[participant.gender].value.saturday_sunday.max_count }}</Message
                                        >
                                        <Message class="meal-run-out-message" v-if="!accommodation[participant.gender].value.saturday_sunday.disable && !accommodation[participant.gender].value.saturday_sunday.warning_show" severity="success"
                                            >{{ accommodation[participant.gender].value.saturday_sunday.booked }} / {{ accommodation[participant.gender].value.saturday_sunday.max_count }}</Message
                                        >
                                    </div>
                                </label>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="3">
                    <AccordionHeader>{{ $t('program_parts') }}</AccordionHeader>
                    <AccordionContent>
                        <div id="ticket_program" class="card flex flex-col gap-4 w-full">
                            <div class="flex flex-col md:flex-row gap-8">
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile">
                                        <input v-model="participant.part_fri" type="checkbox" class="tile-checkbox" @change="recal_price" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-solid fa-p"></i></div>
                                            <span>{{ $t('friday') }}</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile">
                                        <input v-model="participant.part_sat1" type="checkbox" class="tile-checkbox" @change="recal_price" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-solid fa-sun"></i></div>
                                            <span>{{ $t('saturday_morning') }}</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile">
                                        <input v-model="participant.part_sat2" type="checkbox" class="tile-checkbox" @change="recal_price" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-solid fa-s"></i></div>
                                            <span>{{ $t('saturday_afternoon') }}</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="flex gap-2 w-full my-center">
                                    <label class="tile">
                                        <input v-model="participant.part_sat3" type="checkbox" class="tile-checkbox" @change="recal_price" />
                                        <div class="tile-content">
                                            <div class="tile-content-icon"><i class="fa-solid fa-moon"></i></div>
                                            <span>{{ $t('saturday_evening') }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="4">
                    <AccordionHeader>{{ $t('lunch') }} </AccordionHeader>
                    <AccordionContent>
                        <p v-if="participant.food_delivered" class="m-0 mb-2">
                            {{ $t('participant_food_select_disabled') }}
                        </p>

                        <div class="card flex flex-col gap-4 w-full">
                            <div class="flex flex-col md:flex-row gap-8 my-center">
                                <div v-for="(item, index) in meal" :key="index">
                                    <div class="flex gap-2 w-full my-center max-w-[1290px]">
                                        <BlockUI :blocked="participant.food_delivered">
                                            <label class="tile">
                                                <input v-model="participant.meal" type="radio" name="meal" :value="item.id" class="tile-checkbox" />
                                                <div class="tile-content" style="width: 250px">
                                                    <span class="tile-content-title">{{ $t(item.title) }}</span>
                                                    <div class="image-container"><img :src="`/demo/images/lunch/${item.img}`" /></div>
                                                    <div class="flex mb-4 mt-4">
                                                        <div v-for="(alerg, j) in item.alerg" :key="j">
                                                            <Badge class="ml-2" :value="alerg"></Badge>
                                                        </div>
                                                    </div>

                                                    <Message v-if="item.warning_show" severity="warn"> {{ item.ordered }} / {{ item.max_count }} </Message>
                                                    <Message class="meal-run-out-message" v-if="item.disable" severity="error">{{ item.ordered }} / {{ item.max_count }}</Message>
                                                    <Message class="meal-run-out-message" v-if="!item.disable && !item.warning_show" severity="success">{{ item.ordered }} / {{ item.max_count }}</Message>
                                                </div>
                                            </label>
                                        </BlockUI>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="5">
                    <AccordionHeader>{{ $t('intro_page_features_workshops_title') }}</AccordionHeader>
                    <AccordionContent>
                        <div class="card flex flex-col gap-4 w-full">
                            <div class="flex flex-wrap gap-8 max-w-[1000px] mx-auto my-center">
                                <div v-for="(item, index) in workshops" :key="index">
                                    <div class="flex gap-2 w-full my-center">
                                        <label class="tile">
                                            <input v-model="participant.workshop" type="radio" name="workshop" :value="item.id" class="tile-checkbox" />
                                            <div class="tile-content" style="width: 250px">
                                                <span class="tile-content-title">{{ $t(item.title) }}</span>
                                                <div class="image-container mb-4"><img :src="`/demo/images/workshops/${item.img}`" /></div>
                                                <Button class="mb-4" @click="dialogOpen(item)">{{ $t('more_info') }}</Button>
                                                <Message v-if="item.warning_show" severity="warn"> {{ item.ordered }} / {{ item.max_count }}</Message>
                                                <Message class="meal-run-out-message" v-if="item.disable" severity="error">{{ item.ordered }} / {{ item.max_count }}</Message>
                                                <Message class="meal-run-out-message" v-if="!item.disable && !item.warning_show" severity="success">{{ item.ordered }} / {{ item.max_count }}</Message>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="6">
                    <AccordionHeader>{{ $t('ticket') }}</AccordionHeader>
                    <AccordionContent>
                        <div class="qr-container">
                            <div class="qr-container-bg">
                                <qrcode-vue
                                    :value="participant.id"
                                    :level="level"
                                    :render-as="renderAs"
                                    :gradient="gradient"
                                    :gradient-start-color="gradientStartColor"
                                    :gradient-end-color="gradientEndColor"
                                    :gradient-type="gradientType"
                                    :size="qrSize"
                                    :margin="margin"
                                />
                                <!-- SVG logo uprostřed -->
                                <div class="qr-logo">
                                    <svg :width="logoSize" :height="logoSize" viewBox="0 0 14.2875 14.2875">
                                        <defs id="defs1" />
                                        <g inkscape:label="Layer 1" inkscape:groupmode="layer" id="layer1" transform="translate(76.27383,-67.512647)">
                                            <path
                                                style="fill: var(--surface-card)"
                                                d="m -74.039133,78.065249 c -0.280795,-0.02117 -0.559603,-0.113775 -0.792729,-0.263292 -0.251874,-0.161542 -0.448942,-0.377923 -0.580737,-0.637649 -0.07963,-0.156925 -0.125617,-0.300021 -0.154188,-0.479779 -0.01632,-0.102667 -0.0196,-0.329177 -0.0062,-0.43057 0.08901,-0.675265 0.585965,-1.216545 1.264714,-1.37753 0.144352,-0.03424 0.230835,-0.04336 0.408507,-0.04307 0.166432,2.65e-4 0.244117,0.0078 0.376594,0.03644 0.51476,0.11135 0.957852,0.467588 1.16696,0.938216 0.08197,0.184486 0.124078,0.368656 0.132898,0.58127 0.01573,0.379111 -0.103606,0.744514 -0.341355,1.045252 -0.06307,0.07979 -0.197321,0.212732 -0.278724,0.276025 -0.2385,0.185441 -0.53346,0.307661 -0.832971,0.34515 -0.09476,0.01186 -0.270627,0.01649 -0.36274,0.0095 z m 4.816373,2.6e-4 c -0.29142,-0.02207 -0.568199,-0.113786 -0.808985,-0.268062 -0.193566,-0.124021 -0.379525,-0.309333 -0.499555,-0.497817 -0.06953,-0.10918 -2.44337,-4.076053 -2.471655,-4.130325 -0.123835,-0.237611 -0.183956,-0.511429 -0.173314,-0.789346 0.01336,-0.348852 0.126336,-0.657509 0.343119,-0.9374 0.06468,-0.08351 0.234462,-0.245828 0.325348,-0.311046 0.249318,-0.178902 0.512685,-0.280543 0.826072,-0.318806 0.09294,-0.01135 0.326719,-0.0097 0.424464,0.003 0.262563,0.03415 0.507135,0.122032 0.72127,0.259172 0.180717,0.115738 0.365526,0.295618 0.48017,0.467366 0.04621,0.06923 2.457768,4.097134 2.489494,4.158081 0.109575,0.210494 0.165871,0.427307 0.174885,0.673535 0.01478,0.403616 -0.114983,0.784092 -0.372351,1.091804 -0.0656,0.07844 -0.21085,0.214429 -0.291308,0.272749 -0.201938,0.146374 -0.435995,0.249556 -0.67243,0.296437 -0.146188,0.02899 -0.35021,0.0416 -0.495224,0.03062 z m 4.890962,0.0023 c -0.256773,-0.01709 -0.479884,-0.0763 -0.695738,-0.184627 -0.240613,-0.120755 -0.455526,-0.305082 -0.612309,-0.525167 -0.03235,-0.04541 -2.41982,-4.023802 -2.493459,-4.155005 -0.08505,-0.151532 -0.148324,-0.340642 -0.179456,-0.536336 -0.01537,-0.09659 -0.01525,-0.353545 2.09e-4,-0.453976 0.05812,-0.377599 0.231798,-0.70453 0.509598,-0.95925 0.130271,-0.119448 0.260029,-0.206336 0.423311,-0.283453 0.210858,-0.09959 0.401142,-0.148095 0.641382,-0.163505 0.599661,-0.03846 1.173288,0.231278 1.512306,0.711144 0.06445,0.09123 2.472107,4.112935 2.52244,4.21344 0.07292,0.145614 0.121007,0.298478 0.146078,0.464401 0.09486,0.627796 -0.188769,1.247354 -0.730754,1.596266 -0.227895,0.146711 -0.457291,0.230232 -0.725029,0.263975 -0.07536,0.0095 -0.255072,0.01632 -0.318579,0.01209 z"
                                                id="path1"
                                            />
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
            </Accordion>
            <div class="my-right"><Button :label="$t('save')" style="width: 150px" :disabled="!isChanged"></Button></div>
        </div>
    </Fluid>
    <Dialog :header="$t(OpenWorkshopTitle)" v-model:visible="dialogWorkshop" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <img class="mb-4" :src="`/demo/images/workshops/${OpenWorkshop.img}`" />
        <p class="leading-normal m-0">
            {{ $t(OpenWorkshop.desc) }}
        </p>
        <template #footer> </template>
    </Dialog>
</template>

<style scoped>
.qr-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}
.qr-container {
    position: relative;
    display: inline-block;
    width: v-bind(qrSize + 'px');
    height: v-bind(qrSize + 'px');
}

.qr-container-bg {
    position: relative;
    display: inline-block;
    width: v-bind(qrSize + 10+ 'px');
    height: v-bind(qrSize + 10 + 'px');
    padding: 5px;
    background-color: white;
    box-shadow: 0px 0px 20px var(--primary-color-700);
    border-radius: 10px;
}
.qr-logo {
    position: absolute;
    top: 50%;
    left: 50%;
    width: v-bind(logoSize + 'px');
    height: v-bind(logoSize + 'px');
    transform: translate(-50%, -50%);
    background: var(--primary-color-500);
    padding: 5px;
    border-radius: 500%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    overflow: hidden;
}
</style>
