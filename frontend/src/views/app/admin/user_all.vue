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
const deleteUserDialog = ref(false);
const user = ref();
const admin_user = ref(false);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});
let user_info_title;

async function load_all_user() {
    const api = await api_post(config.endpoint_admin, { method: 'user_all' });
    if (config.debug) {
        console.log('API [user_all]: ');
        console.log(api);
    }
    if (api.result) {
        users.value = api.response;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t('error_comm_database'), life: config.toast_lifetime });
    }
}

async function promote_user(prod) {
    const api = await api_post(config.endpoint_admin, { method: 'change_rank_user', parameters: { email: prod.email, promote: true } });
    if (config.debug) {
        console.log('API [change_rank_user]: ');
        console.log(api);
    }
    if (api.result) {
        const index = users.value.findIndex((user) => user.email === prod.email);
        prod.admin_user = 1;
        users.value[index] = prod;
        toast.add({ severity: 'success', summary: i18n.global.t('admin_users_promote_title'), detail: i18n.global.t('admin_user_promote_desc'), life: config.toast_lifetime });
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.error_title), detail: i18n.global.t(api.response.error_desc), life: config.toast_lifetime });
    }
}

async function DummyUser() {
    const api = await api_post(config.endpoint_admin, { method: 'create_test_user' });
}

function confirmDeleteUser(prod) {
    user.value = prod;
    deleteUserDialog.value = true;
}

async function deleteUser() {
    const api = await api_post(config.endpoint_admin, { method: 'delete_normal_user', parameters: { email: user.value.email } });
    if (config.debug) {
        console.log('API [delete_normal_user]: ');
        console.log(api);
    }
    if (api.result) {
        users.value = users.value.filter((val) => val.email !== user.value.email);
        deleteUserDialog.value = false;
        user.value = {};
        toast.add({ severity: 'success', summary: i18n.global.t('sucessfull'), detail: i18n.global.t('sucessfull_admin_user_successful_deletes'), life: config.toast_lifetime });
    } else {
        deleteUserDialog.value = false;
        toast.add({ severity: 'error', summary: i18n.global.t(api.response.error_title), detail: i18n.global.t(api.response.error_desc), life: config.toast_lifetime });
    }
}
onMounted(() => {
    load_all_user();
});
</script>

<template>
    <div className="card">
        <div class="font-semibold text-xl mb-4">{{ $t('menu_all_users') }}</div>
        <Button v-if="config.debug" severity="success" @click="DummyUser">Add Dummy User</Button>
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

            <Column field="FirstName" :header="$t('first_name')" sortable style="min-width: 6rem"> </Column>
            <Column field="LastName" :header="$t('last_name')" sortable style="min-width: 3rem"> </Column>
            <Column :header="$t('admin_user')" :exportable="false" style="min-width: 3rem">
                <template #body="slotProps">
                    <Button v-if="slotProps.data.admin_user == 0" @click="promote_user(slotProps.data)"><i class="fa-solid fa-caret-up"></i>{{ $t('promote') }}</Button>
                    <Button v-if="slotProps.data.admin_user == 1" severity="success" disabled>{{ $t('promoted') }}</Button>
                </template>
            </Column>
            <Column :exportable="false" style="min-width: 2rem">
                <template #body="slotProps">
                    <Button v-if="slotProps.data.admin_user == 0" icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteUser(slotProps.data)" />
                </template>
            </Column>
        </DataTable>
    </div>

    <Dialog v-model:visible="userDialog" :style="{ width: '90%' }" :header="user_info_title" :modal="true">
        <div class="flex items-center gap-4">
            <div class="font-semibold">{{ $t('admin_user') }}</div>
            <ToggleSwitch v-model="admin_user" />
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
