<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { DotLottieVue } from '@lottiefiles/dotlottie-vue';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import { useRouter } from 'vue-router';

const { api_post } = useApi();
const tOptionScannerTranslate = (option) => i18n.global.t(option);

const toast = useToast();
const router = useRouter();
/*** detection handling ***/

const scanner_options_select = ref([]);
const scanner_options_selected = ref();
const result = ref('');
const ticketDiaglog = ref(false);

const ticketData = ref({
    result: false,
    email: '',
    price: '',
    meal: '',
    desc: ''
});

function onDetect(detectedCodes) {
    console.log(detectedCodes);
    result.value = JSON.stringify(detectedCodes.map((code) => code.rawValue));
    scanner_read(detectedCodes[0].rawValue);
}

async function scanner_read(id) {
    ticketData.value = {
        result: false,
        email: '',
        price: '',
        meal: '',
        desc: ''
    };
    const api = await api_post(config.endpoint_ticket, { method: 'qr_scanned', parameters: { id: id } });
    if (config.debug) {
        console.log('API [qr_scanned]: ');
        console.log(api);
    }
    if (api.result) {
        if (api.scanner_type == 'scanner_admin') {
            router.push(api.link);
        } else {
            ticketData.value.result = api.result;
            ticketData.value.email = api.email;
            ticketData.value.price = api.pay;
            ticketData.value.meal = api.meal;
            ticketData.value.desc = api.response;
            ticketDiaglog.value = true;
        }
    } else {
        ticketData.value.result = api.result;
        ticketData.value.email = api.email;
        ticketData.value.price = api.pay;
        ticketData.value.meal = api.meal;
        ticketData.value.desc = api.response;
        ticketDiaglog.value = true;
    }
}

function onticketDiaglogClose() {
    ticketDiaglog.value = false;
    result.value = '';
}

/*** select camera ***/

const selectedConstraints = ref({ facingMode: 'environment' });
const defaultConstraintOptions = [
    { label: 'rear camera', constraints: { facingMode: 'environment' } },
    { label: 'front camera', constraints: { facingMode: 'user' } }
];
const constraintOptions = ref(defaultConstraintOptions);

async function onCameraReady() {
    // NOTE: on iOS we can't invoke `enumerateDevices` before the user has given
    // camera access permission. `QrcodeStream` internally takes care of
    // requesting the permissions. The `camera-on` event should guarantee that this
    // has happened.
    const devices = await navigator.mediaDevices.enumerateDevices();
    const videoDevices = devices.filter(({ kind }) => kind === 'videoinput');

    constraintOptions.value = [
        ...defaultConstraintOptions,
        ...videoDevices.map(({ deviceId, label }) => ({
            label: `${label}`,
            constraints: { deviceId }
        }))
    ];

    error.value = '';
}

/*** track functons ***/

function paintOutline(detectedCodes, ctx) {
    for (const detectedCode of detectedCodes) {
        const [firstPoint, ...otherPoints] = detectedCode.cornerPoints;

        ctx.strokeStyle = 'red';

        ctx.beginPath();
        ctx.moveTo(firstPoint.x, firstPoint.y);
        for (const { x, y } of otherPoints) {
            ctx.lineTo(x, y);
        }
        ctx.lineTo(firstPoint.x, firstPoint.y);
        ctx.closePath();
        ctx.stroke();
    }
}
function paintBoundingBox(detectedCodes, ctx) {
    for (const detectedCode of detectedCodes) {
        const {
            boundingBox: { x, y, width, height }
        } = detectedCode;

        ctx.lineWidth = 2;
        ctx.strokeStyle = '#007bff';
        ctx.strokeRect(x, y, width, height);
    }
}
function paintCenterText(detectedCodes, ctx) {
    for (const detectedCode of detectedCodes) {
        const { boundingBox, rawValue } = detectedCode;

        const centerX = boundingBox.x + boundingBox.width / 2;
        const centerY = boundingBox.y + boundingBox.height / 2;

        const fontSize = Math.max(12, (50 * boundingBox.width) / ctx.canvas.width);

        ctx.font = `bold ${fontSize}px sans-serif`;
        ctx.textAlign = 'center';

        ctx.lineWidth = 3;
        ctx.strokeStyle = '#35495e';
        ctx.strokeText(detectedCode.rawValue, centerX, centerY);

        ctx.fillStyle = '#5cb984';
        ctx.fillText(rawValue, centerX, centerY);
    }
}
const trackFunctionOptions = [
    { text: 'nothing (default)', value: undefined },
    { text: 'outline', value: paintOutline },
    { text: 'centered text', value: paintCenterText },
    { text: 'bounding box', value: paintBoundingBox }
];
const trackFunctionSelected = ref(trackFunctionOptions[2]);

async function load_scan_info() {
    const api = await api_post(config.endpoint_admin, { method: 'get_qr_reader_info', parameters: {} });
    if (config.debug) {
        console.log('API [get_qr_reader_info]: ');
        console.log(api);
    }
    if (api.result) {
        scanner_options_select.value = api.response.scanner_options;
        scanner_options_selected.value = api.response.user_info.scanner;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

async function update_scanner_options() {
    const api = await api_post(config.endpoint_admin, { method: 'update_user_scanner', parameters: { scanner: scanner_options_selected.value } });
    if (config.debug) {
        console.log('API [update_user_scanner]: ');
        console.log(api);
    }
    if (api.result) {
        toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('sucessfull_admin_scanner_options_updated'), life: config.toast_lifetime });
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.error_title), detail: i18n.global.t(api.response.error_desc), life: config.toast_lifetime });
    }
    load_scan_info();
}

onMounted(() => {
    load_scan_info();
});

/*** barcode formats ***/

const barcodeFormats = ref({
    aztec: false,
    code_128: false,
    code_39: false,
    code_93: false,
    codabar: false,
    databar: false,
    databar_expanded: false,
    data_matrix: false,
    dx_film_edge: false,
    ean_13: false,
    ean_8: false,
    itf: false,
    maxi_code: false,
    micro_qr_code: false,
    pdf417: false,
    qr_code: true,
    rm_qr_code: false,
    upc_a: false,
    upc_e: false,
    linear_codes: false,
    matrix_codes: false
});
const selectedBarcodeFormats = computed(() => {
    return Object.keys(barcodeFormats.value).filter((format) => barcodeFormats.value[format]);
});

/*** error handling ***/

const error = ref('');

function onError(err) {
    error.value = `[${err.name}]: `;

    if (err.name === 'NotAllowedError') {
        error.value += 'you need to grant camera access permission';
    } else if (err.name === 'NotFoundError') {
        error.value += 'no camera on this device';
    } else if (err.name === 'NotSupportedError') {
        error.value += 'secure context required (HTTPS, localhost)';
    } else if (err.name === 'NotReadableError') {
        error.value += 'is the camera already in use?';
    } else if (err.name === 'OverconstrainedError') {
        error.value += 'installed cameras are not suitable';
    } else if (err.name === 'StreamApiNotSupportedError') {
        error.value += 'Stream API is not supported in this browser';
    } else if (err.name === 'InsecureContextError') {
        error.value += 'Camera access is only permitted in secure context. Use HTTPS or localhost rather than HTTP.';
    } else {
        error.value += err.message;
    }
}
</script>

<style scoped>
.error {
    font-weight: bold;
    color: red;
}
.barcode-format-checkbox {
    margin-right: 10px;
    white-space: nowrap;
    display: inline-block;
}
.qr-box {
    position: relative;
    width: 100%;
    height: 100%;
}
.qr-scanner-select-camera {
    position: absolute;
    top: 20px;
    right: 10px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
    width: auto;
    z-index: 1;
}
.qr-scanner-select-permissions {
    position: absolute;
    bottom: 10px;
    z-index: 1;
}
</style>

<template>
    <Select v-model="selectedConstraints" :options="constraintOptions" optionLabel="label" placeholder="Select" style="width: 100%" />
    <div>
        <qrcode-stream :constraints="selectedConstraints" :track="trackFunctionSelected.value" :formats="selectedBarcodeFormats" @error="onError" @detect="onDetect" @camera-on="onCameraReady" />
    </div>
    <div>
        <Select v-model="scanner_options_selected" :options="scanner_options_select" :optionLabel="tOptionScannerTranslate" placeholder="Select" @change="update_scanner_options" style="width: 100%">
            <template #option="slotProps">
                <div class="flex items-center">
                    <div>{{ $t(slotProps.option) }}</div>
                </div>
            </template>
        </Select>

        <p class="error">{{ error }}</p>
    </div>

    <Dialog v-model:visible="ticketDiaglog" :style="{ width: '90%' }" @hide="onticketDiaglogClose" :header="ticketData.email" :modal="true">
        <div class="flex flex-col items-center justify-center">
            <DotLottieVue v-if="!ticketData.result" style="height: 250px; width: 250px" :speed="1.5" autoplay src="https://lottie.host/656da98f-81da-429c-a167-06b4f81191bf/6ht3bLPKkk.json" />
            <DotLottieVue v-else style="height: 250px; width: 250px" :speed="1.7" autoplay src="https://lottie.host/75ef43fd-dcdd-409b-82bc-823226c80005/EEKEtUtjLY.json" />
            <label class="text-2xl font-bold text-center mb-8">{{ $t(ticketData.desc) }}</label>
            <label class="text-xl text-center mt-4">{{ $t('total_price') }}: {{ ticketData.price }} {{ $t('currency_shortcut') }}</label>
            <label class="text-xl text-center mt-4">{{ $t('meal') }}: {{ $t(ticketData.meal) }}</label>
        </div>
    </Dialog>
</template>
