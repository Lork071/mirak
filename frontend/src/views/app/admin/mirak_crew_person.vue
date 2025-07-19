<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

const role_not_pay = ref('organizer');
const router = useRoute();
const toast = useToast();
const { api_post } = useApi();
const crew_persons = ref();
const showConfirm = ref(false);

onMounted(() => {
    load_mirak_crew_person(router.query.id);
});

async function load_mirak_crew_person(id) {
    const api = await api_post(config.endpoint_volunteer, { method: 'read_mirak_crew_person', parameters: { id: id } });
    if (config.debug) {
        console.log('API [read_mirak_crew_person]: ');
        console.log(api);
    }
    if (api.result) {
        crew_persons.value = api.response;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

function downloadVCard() {
    if (!crew_persons.value) return;
    const vcard = `BEGIN:VCARD
VERSION:3.0
FN:${crew_persons.value.first_name} ${crew_persons.value.last_name}
EMAIL:${crew_persons.value.email}
TEL:${crew_persons.value.phone_number}
END:VCARD`;
    const blob = new Blob([vcard], { type: 'text/vcard' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${crew_persons.value.first_name}_${crew_persons.value.last_name}.vcf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function onContacted() {
    showConfirm.value = true;
}

async function confirmContacted() {
    showConfirm.value = false;
    const api = await api_post(config.endpoint_volunteer, {
        method: 'mark_as_contacted',
        parameters: {
            id: crew_persons.value.id,
            processed: crew_persons.value.processed === 1 ? 0 : 1
        }
    });
    if (config.debug) {
        console.log('API [mark_as_contacted]: ');
        console.log(api);
    }
    if (api.result) {
        window.location.reload();
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response.desc), life: config.toast_lifetime });
    }
}

function cancelConfirm() {
    showConfirm.value = false;
}
</script>

<template>
    <div class="card mx-auto p-6 rounded-lg shadow-lg bg-surface-0 dark:bg-surface-900">
        <div class="text-center mb-6">
            <span class="font-semibold text-2xl">{{ crew_persons?.first_name }} {{ crew_persons?.last_name }}</span>
        </div>
        <table class="w-full mb-6 border-separate border-spacing-y-2">
            <tbody>
                <tr>
                    <td class="font-medium text-muted-color w-1/3">{{ $t('email') }}</td>
                    <td>{{ crew_persons?.email }}</td>
                </tr>
                <tr>
                    <td class="font-medium text-muted-color">{{ $t('phone_number') }}</td>
                    <td>{{ crew_persons?.phone_number }}</td>
                </tr>
                <tr v-if="crew_persons?.note && crew_persons.note.trim().length > 0">
                    <td class="font-medium text-muted-color align-top">{{ $t('note') }}</td>
                    <td>
                        <div style="max-width: 100%; word-break: break-word; white-space: pre-line">
                            {{ crew_persons.note.slice(0, 2500) }}
                        </div>
                        <span class="text-xs text-muted-color"> {{ crew_persons.note.length }}/2500 </span>
                    </td>
                </tr>
            </tbody>
        </table>
        <Dialog v-model:visible="showConfirm" modal header="Potvrzení" :closable="false">
            <div>
                <span v-if="crew_persons?.processed === 1">
                    {{ $t('confirm_mark_as_not_contacted', { name: crew_persons?.first_name + ' ' + crew_persons?.last_name }) }}
                </span>
                <span v-else>
                    {{ $t('confirm_mark_as_contacted', { name: crew_persons?.first_name + ' ' + crew_persons?.last_name }) }}
                </span>
            </div>
            <template #footer>
                <Button label="OK" severity="success" @click="confirmContacted" />
                <Button label="Zrušit" severity="secondary" @click="cancelConfirm" />
            </template>
        </Dialog>
        <div class="flex justify-end gap-2">
            <Button :label="crew_persons?.processed === 1 ? $t('btn_mark_as_processed') : $t('btn_mark_as_not_processed')" :severity="crew_persons?.processed === 1 ? 'success' : 'danger'" :disabled="!crew_persons" @click="onContacted" />
            <Button :label="$t('import_to_contacts')" :disabled="!crew_persons" @click="downloadVCard" />
        </div>
    </div>
</template>
