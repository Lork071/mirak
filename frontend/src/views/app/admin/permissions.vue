<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';

const meal_operation = ref('scan_meal');
const scanner_page = ref('qrscanner');
const scan_food_enable = ref(false);
const tOptionScannerTranslate = (option) => i18n.global.t(option);

const toast = useToast();
const dt = ref();
const Permissions = ref([]);
const PermissionDialog = ref(false);
const deletePermissionDialog = ref(false);
const Permission = ref({});
const selectedPermissions = ref();
const permissionsPageAll = ref(null);
const permissionsPageSelected = ref(null);
const permissionsOperationsAll = ref(null);
const permissionsOperationsSelected = ref(null);
const permissionsFoodAll = ref(null);
const permissionsFoodSelected = ref(null);
const permissionsScannerOptions = ref([]);
const permissionsScannerSelected = ref(null);
const ScannerOption_show = ref(false);
let SaveUpdatefunction = '';
const submitted = ref(false);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});
const { api_post } = useApi();

onMounted(() => {
    getPermissions();
});
async function getPermissions() {
    const api = await api_post(config.endpoint_admin, { method: 'permission_read' });
    if (config.debug) {
        console.log('API [permission_read]: ');
        console.log(api);
    }
    if (api.result) {
        Permissions.value = api.response.permissions;
        permissionsPageAll.value = api.response.permissions_pages;
        permissionsOperationsAll.value = api.response.permissions_operations;
        permissionsFoodAll.value = Object.keys(api.response.food_scan);
        permissionsScannerOptions.value = api.response.scanners;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

async function savePermission() {
    submitted.value = true;
    PermissionDialog.value = false;
    const api = await api_post(config.endpoint_admin, {
        method: SaveUpdatefunction,
        parameters: {
            pages: permissionsPageSelected.value,
            operations: permissionsOperationsSelected.value,
            food_scan: permissionsFoodSelected.value,
            scanner_default: permissionsScannerSelected.value,
            name: Permission.value.name,
            id: Permission.value.id
        }
    });
    if (api.result == true) {
        toast.add({ severity: 'success', summary: i18n.global.t(api.response.title), detail: i18n.global.t(api.response.message), life: config.toast_lifetime });
        getPermissions();
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.title), detail: i18n.global.t(api.response.message), life: config.toast_lifetime });
    }
}

async function deletePermission() {
    const api = await api_post(config.endpoint_admin, { method: 'permission_delete', parameters: { id: Permission.value.id } });
    if (api.result) {
        Permissions.value = Permissions.value.filter((val) => val.id !== Permission.value.id);
        deletePermissionDialog.value = false;
        Permission.value = {};
        toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('admin_permissions_deleted'), life: config.toast_lifetime });
        getPermissions();
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('admin_permissions_not_deleted'), life: config.toast_lifetime });
    }
}

function openNew() {
    Permission.value = {};
    SaveUpdatefunction = 'permission_create';
    submitted.value = false;
    PermissionDialog.value = true;
}

function hideDialog() {
    PermissionDialog.value = false;
    submitted.value = false;
}

function editPermission(prod) {
    Permission.value = { ...prod };
    SaveUpdatefunction = 'permission_update';
    if (prod.pages.length > 0) {
        permissionsPageSelected.value = prod.pages;
    } else {
        permissionsPageSelected.value = [];
    }
    if (prod.operations.length > 0) {
        permissionsOperationsSelected.value = prod.operations;
    } else {
        permissionsOperationsSelected.value = [];
    }
    permissionsFoodSelected.value = prod.food_scan;
    permissionsOperationsSelected.value = prod.operations;

    permissionsScannerSelected.value = prod.scanner_default;
    PermissionDialog.value = true;
    scan_food_enable.value = permissionsOperationsSelected.value.includes(meal_operation.value);
    ScannerOption_show.value = permissionsPageSelected.value.includes(scanner_page.value);
}

function PagesPremissionChange() {
    ScannerOption_show.value = permissionsPageSelected.value.includes(scanner_page.value);
}

function confirmDeletePermission(prod) {
    Permission.value = prod;
    deletePermissionDialog.value = true;
}
</script>

<template>
    <div>
        <div className="card">
            <div class="font-semibold text-xl mb-4">{{ $t('menu_admin_permissions') }}</div>
            <DataTable
                ref="dt"
                v-model:selection="selectedPermissions"
                :value="Permissions"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} Permissions"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <Button :label="$t('create')" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
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
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editPermission(slotProps.data)" />
                    </template>
                </Column>
                <Column field="name" :header="$t('title')" sortable style="min-width: 12rem"></Column>

                <Column :exportable="false" style="min-width: 3rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeletePermission(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="PermissionDialog" :style="{ width: '90%' }" :header="$t('permissions_details')" :modal="true">
            <div class="flex flex-col gap-6">
                <div>
                    <label for="name" class="block font-bold mb-3">{{ $t('title') }}</label>
                    <InputText id="name" v-model.trim="Permission.name" required="true" autofocus :invalid="submitted && !Permission.name" fluid />
                    <small v-if="submitted && !Permission.name" class="text-red-500">{{ $t('title_is_required') }}</small>
                </div>
                <label for="name" class="block font-bold mb-3">{{ $t('Pages') }}</label>
                <MultiSelect v-model="permissionsPageSelected" :options="permissionsPageAll" optionLabel="name" :placeholder="$t('select_option')" :filter="true" @change="PagesPremissionChange()">
                    <template #value="slotProps">
                        <div class="inline-flex items-center py-1 px-2 bg-primary text-primary-contrast rounded-border mr-2" v-for="option of slotProps.value" :key="option">
                            <div>{{ option }}</div>
                        </div>
                        <template v-if="!slotProps.value || slotProps.value.length === 0">
                            <div class="p-1">{{ $t('select_option') }}</div>
                        </template>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center">
                            <div>{{ slotProps.option }}</div>
                        </div>
                    </template>
                </MultiSelect>
                <label for="name" class="block font-bold mb-1">{{ $t('operations') }}</label>
                <MultiSelect v-model="permissionsOperationsSelected" :options="permissionsOperationsAll" optionLabel="name" :placeholder="$t('select_option')" :filter="true">
                    <template #value="slotProps">
                        <div class="inline-flex items-center py-1 px-2 bg-primary text-primary-contrast rounded-border mr-2" v-for="option of slotProps.value" :key="option">
                            <div>{{ option }}</div>
                        </div>
                        <template v-if="!slotProps.value || slotProps.value.length === 0">
                            <div class="p-1">{{ $t('select_option') }}</div>
                        </template>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center">
                            <div>{{ slotProps.option }}</div>
                        </div>
                    </template>
                </MultiSelect>
                <div v-if="ScannerOption_show">
                    <label for="name" class="block font-bold mb-1">{{ $t('default_scanner') }}</label>
                    <Select v-model="permissionsScannerSelected" :options="permissionsScannerOptions" :optionLabel="tOptionScannerTranslate" placeholder="Select">
                        <template #option="slotProps">
                            <div class="flex items-center">
                                <div>{{ $t(slotProps.option) }}</div>
                            </div>
                        </template>
                    </Select>
                </div>

                <div v-if="scan_food_enable">
                    <label for="name" class="block font-bold mb-1">{{ $t('food') }}</label>
                    <Select v-model="permissionsFoodSelected" :options="permissionsFoodAll" optionLabel="" placeholder="Select" />
                </div>
            </div>

            <template #footer>
                <Button :label="$t('cancel')" icon="pi pi-times" text @click="hideDialog" />
                <Button :label="$t('save')" icon="pi pi-check" @click="savePermission" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deletePermissionDialog" :style="{ width: '350px' }" :header="$t('confirm')" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="Permission"
                    >{{ $t('dialog_delete') }} <b>{{ Permission.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button :label="$t('no')" icon="pi pi-times" text @click="deletePermissionDialog = false" />
                <Button :label="$t('yes')" icon="pi pi-check" @click="deletePermission" />
            </template>
        </Dialog>
    </div>
</template>
