<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';

const toast = useToast();
const { api_post } = useApi();
const participants = ref();
const OpenWorkshop = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

async function load_all_participant() {
    const api = await api_post(config.endpoint_ticket, { method: 'read_all_participant' });
    if (config.debug) {
        console.log('API [read_all_participant]: ');
        console.log(api);
    }
    if (api.result) {
        participants.value = api.response;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

function open_participant(id_ticket) {
    window.open('/app/participant?id=' + id_ticket, '_blank');
}

onMounted(() => {
    load_all_participant();
});
</script>

<template>
    <div className="card">
        <div class="font-semibold text-xl mb-4">{{ $t('all_participant') }}</div>
        <DataTable
            ref="dt"
            :value="participants"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[10, 50, 100]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} Errors"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0"></h4>
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" :placeholder="$t('search')" />
                    </IconField>
                </div>
            </template>
            <Column :exportable="false" style="min-width: 3rem">
                <template #body="slotProps">
                    <Button icon="fa-solid fa-pen-to-square" outlined rounded class="mr-2" @click="open_participant(slotProps.data.id)" />
                </template>
            </Column>
            <Column field="email" :header="$t('email')" sortable style="min-width: 6rem"> </Column>
            <Column field="first_name" :header="$t('first_name')" sortable style="min-width: 6rem"> </Column>
            <Column field="last_name" :header="$t('last_name')" sortable style="min-width: 3rem"> </Column>
        </DataTable>
    </div>
</template>
