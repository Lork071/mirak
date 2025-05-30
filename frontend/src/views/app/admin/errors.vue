<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import { useUserStore } from '@/service/user';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

const toast = useToast();
const user_data = useUserStore();
const { api_post } = useApi();
const errors = ref();
const error = ref();
const dt = ref();
const deleteErrorDialog = ref(false);
const deleteErrorsDialog = ref(false);
const detailErrorInfoDialog = ref(false);
const selectedErrors = ref();
let error_title;
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

load_errors();

async function load_errors() {
    const api = await api_post(config.endpoint_admin, { method: 'read_errors' });
    if (api.result) {
        errors.value = api.response;
    }
}

function openError(prod) {
    error.value = { ...prod };
    error_title = 'Error ID: ' + error.value.id;
    detailErrorInfoDialog.value = true;
}

function confirmDeleteSelected() {
    deleteErrorsDialog.value = true;
}

function confirmDeleteError(prod) {
    error.value = prod;
    deleteErrorDialog.value = true;
}

async function deleteError() {
    const api = await api_post(config.endpoint_admin, { method: 'error_delete', parameters: { condition: '`id` = ' + error.value.id } });

    if (api.result) {
        errors.value = errors.value.filter((val) => val.id !== error.value.id);
        deleteErrorDialog.value = false;
        error.value = {};
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
    }
}

async function deleteSelectedErrors() {
    let condition_string = '`id` IN (';
    selectedErrors.value.forEach((element, index, arr) => {
        console.log(element.id);
        if (index === arr.length - 1) {
            condition_string += element.id + ')';
        } else {
            condition_string += element.id + ', ';
        }
    });

    const api = await api_post(config.endpoint_admin, { method: 'error_delete', parameters: { condition: condition_string } });
    if (api.result) {
        errors.value = errors.value.filter((val) => !selectedErrors.value.includes(val));
        deleteErrorsDialog.value = false;
        selectedErrors.value = null;
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Errors Deleted', life: 3000 });
    }
}

function exportCSV() {
    dt.value.exportCSV();
}

async function deleteAll() {
    const api = await api_post(config.endpoint_admin, { method: 'error_delete', parameters: { condition: '1' } });

    if (api.result) {
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
    }
}

async function generateError() {
    const api = await api_post(config.endpoint_admin, { method: 'error_generate' });

    if (api.result) {
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
    }
}
</script>

<template>
    <div className="card">
        <div class="font-semibold text-xl mb-4">{{ $t('menu_errors') }}</div>
        <Toolbar class="mb-6">
            <template #start>
                <Button :label="$t('admin_error_delete_btn')" icon="pi pi-trash" severity="secondary" class="mr-2" @click="confirmDeleteSelected" :disabled="!selectedErrors || !selectedErrors.length" />
                <Button :label="$t('admin_error_delete_all_btn')" icon="pi pi-trash" severity="secondary" class="mr-2" @click="deleteAll" />
            </template>

            <template #end>
                <Button :label="$t('admin_error_export')" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
            </template>
        </Toolbar>
        <Button v-if="config.debug" :label="$t('admin_error_generate_error')" icon="pi pi-trash" severity="secondary" class="mr-2" @click="generateError" />
        <DataTable
            ref="dt"
            v-model:selection="selectedErrors"
            :value="errors"
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
                    <h4 class="m-0">{{ $t('admin_error_backend_errors') }}</h4>
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" :placeholder="$t('search')" />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
            <Column field="type" header="Type" sortable style="min-width: 6rem">
                <template #body="slotProps">
                    <template v-if="slotProps.data.type == 'Error'">
                        <i class="fa-solid fa-circle-xmark" style="color: #ff0000"></i>
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-triangle-exclamation" style="color: #ffd43b"></i>
                    </template>
                </template>
            </Column>
            <Column field="time" header="Time" sortable style="min-width: 3rem"> </Column>
            <Column field="message" header="Message" sortable style="min-width: 10rem"></Column>
            <Column field="file" header="File" sortable style="min-width: 12rem"> </Column>
            <Column field="line" header="Line" sortable style="min-width: 12rem"> </Column>
            <Column :exportable="false" style="min-width: 12rem">
                <template #body="slotProps">
                    <Button icon="fa-solid fa-up-right-and-down-left-from-center" outlined rounded class="mr-2" @click="openError(slotProps.data)" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteError(slotProps.data)" />
                </template>
            </Column>
        </DataTable>
    </div>

    <Dialog v-model:visible="deleteErrorsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span>Are you sure you want to delete the selected Errors?</span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="deleteErrorsDialog = false" />
            <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedErrors" />
        </template>
    </Dialog>

    <Dialog v-model:visible="deleteErrorDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="error"
                >Are you sure you want to delete <b>{{ error.id }}</b
                >?</span
            >
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" text @click="deleteErrorDialog = false" />
            <Button label="Yes" icon="pi pi-check" @click="deleteError" />
        </template>
    </Dialog>

    <Dialog v-model:visible="detailErrorInfoDialog" :style="{ width: '450px' }" :header="error_title" :modal="true">
        <div class="flex flex-col gap-6">
            <h2>{{ error.type }}</h2>
            <span>Message: {{ error.message }}</span>
            <span>file: {{ error.file }}</span>
            <span>Line: {{ error.line }}</span>
            <span>User: {{ error.user }}</span>
        </div>
    </Dialog>
</template>
