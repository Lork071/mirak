<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';

const toast = useToast();
const { api_post } = useApi();
const users = ref();
const selectedUser = ref();
const userDialog = ref(false);
const user = ref();
const admin_user = ref(false);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});
let user_info_title;
const deleteUserDialog = ref(false);
const dropdownAllPermissions = ref(null);
const dropdownUserPermission = ref(null);

async function load_all_user() {
    const api = await api_post(config.endpoint_admin, { method: 'user_admin' });
    if (config.debug) {
        console.log('API [user_admin]: ');
        console.log(api);
    }
    if (api.result) {
        users.value = api.response.admin_users_info;
        dropdownAllPermissions.value = api.response.all_permissions;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}
async function user_permission_update() {
    const api = await api_post(config.endpoint_admin, { method: 'permission_user_update', parameters: { email: user.value.email, permission: dropdownUserPermission.value.id } });
    if (config.debug) {
        console.log('API [permission_user_update]: ');
        console.log(api);
    }
    if (api.result) {
        toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('sucessfull_admin_permissions_user_updated'), life: config.toast_lifetime });
        load_all_user();
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.error_title), detail: i18n.global.t(api.response.error_desc), life: config.toast_lifetime });
    }
}

async function unpromote_user(one_user) {
    const api = await api_post(config.endpoint_admin, { method: 'change_rank_user', parameters: { email: one_user.email, promote: false } });
    if (config.debug) {
        console.log('User all response from api: ');
        console.log(api);
    }
    if (api.result) {
        load_all_user();
        userDialog.value = false;
        if (admin_user.value) {
            toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('admin_user_promote_change_desc'), life: config.toast_lifetime });
        } else {
            toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('sucessfull_admin_user_unpromote_change_desc'), life: config.toast_lifetime });
        }
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.error_title), detail: i18n.global.t(api.response.error_desc), life: config.toast_lifetime });
    }
}
async function deleteUser() {
    const api = await api_post(config.endpoint_admin, { method: 'delete_admin_user', parameters: { email: user.value.email } });
    deleteUserDialog.value = false;
    if (config.debug) {
        console.log('API [delete_admin_user]:');
        console.log(api);
    }
    if (api.result) {
        toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('sucessfull_admin_user_successful_deletes'), life: config.toast_lifetime });
        load_all_user();
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.error_title), detail: i18n.global.t(api.response.error_desc), life: config.toast_lifetime });
    }
}

function open_user(prod) {
    user.value = { ...prod };
    user_info_title = user.value.FirstName + ' ' + user.value.LastName;
    if (user.value.admin_user) {
        admin_user.value = true;
    } else {
        admin_user.value = false;
    }
    dropdownUserPermission.value = dropdownAllPermissions.value.find((item) => item.id === user.value.permission);
    userDialog.value = true;
}
function confirmDeleteUser(prod) {
    user.value = prod;
    deleteUserDialog.value = true;
}

onMounted(() => {
    load_all_user();
});
</script>

<template>
    <div className="card">
        <div class="font-semibold text-xl mb-4">{{ $t('menu_admin_users') }}</div>
        <DataTable
            ref="dt"
            v-model:selection="selectedUser"
            :value="users"
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
                    <Button icon="fa-solid fa-up-right-and-down-left-from-center" outlined rounded class="mr-2" @click="open_user(slotProps.data)" />
                </template>
            </Column>
            <Column field="FirstName" :header="$t('first_name')" sortable style="min-width: 6rem"> </Column>
            <Column field="LastName" :header="$t('last_name')" sortable style="min-width: 3rem"> </Column>
            <Column :header="$t('admin_user')" :exportable="false" style="min-width: 3rem">
                <template #body="slotProps">
                    <Button severity="danger" @click="unpromote_user(slotProps.data)"><i class="fa-solid fa-caret-down"></i>{{ $t('demote') }}</Button>
                </template>
            </Column>
            <Column :exportable="false" style="min-width: 12rem">
                <template #body="slotProps">
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteUser(slotProps.data)" />
                </template>
            </Column>
        </DataTable>
    </div>

    <Dialog v-model:visible="userDialog" :style="{ width: '90%' }" :header="user_info_title" :modal="true">
        <div class="flex items-center gap-4">
            <div class="font-semibold text-xl">{{ $t('menu_admin_permissions') }}</div>
            <Select v-model="dropdownUserPermission" :options="dropdownAllPermissions" optionLabel="name" :placeholder="$t('select_option')" @change="user_permission_update" />
        </div>
    </Dialog>

    <Dialog v-model:visible="deleteUserDialog" :style="{ width: '350px' }" :header="$t('confirm')" :modal="true">
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="user"
                >{{ $t('dialog_delete_user') }}: <b>{{ user.FirstName + ' ' + user.LastName }}</b
                >?</span
            >
        </div>
        <template #footer>
            <Button :label="$t('no')" icon="pi pi-times" text @click="deleteUserDialog = false" />
            <Button :label="$t('yes')" icon="pi pi-check" @click="deleteUser" />
        </template>
    </Dialog>
</template>
