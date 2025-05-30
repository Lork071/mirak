<script setup>
import i18n from '@/service/i18n';
import { useUserStore } from '@/service/user';
import { computed, ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const user_data = useUserStore();

const model = ref([
    {
        items: [{ label: 'my_mirak_plus', id: 'mirak_plus', icon: 'pi pi-fw pi-home', to: '/app' }]
    },
    {
        label: 'actual_event',
        to: '/event',
        items: [
            { label: 'my_tickets', icon: 'fa-solid fa-ticket', to: '/uikit/formlayout' },
            { label: 'menu_scheduler', icon: 'pi pi-fw pi-check-square', to: '/uikit/input' }
        ]
    },
    {
        label: 'menu_control_panels',
        id: 'admin',
        icon: 'pi pi-fw pi-briefcase',
        to: '/admin',
        items: [
            {
                label: 'qr_scanner',
                id: 'qr_scanner',
                icon: 'fa-solid fa-qrcode',
                to: '/app/qrscanner'
            },
            {
                label: 'actual_event',
                id: 'actual_event',
                icon: 'fa-regular fa-calendar',
                items: [
                    {
                        label: 'all_participant',
                        id: 'all_participant',
                        icon: 'fa-solid fa-users-line',
                        to: '/app/all_participant'
                    }
                ]
            },
            {
                label: 'menu_users',
                id: 'users',
                icon: 'pi pi-fw pi-user',
                items: [
                    {
                        label: 'menu_all_users',
                        id: 'all_users',
                        icon: 'fa-solid fa-users',
                        to: '/app/user_all'
                    },
                    {
                        label: 'menu_admin_users',
                        id: 'admin_users',
                        icon: 'fa-solid fa-user-tie',
                        to: '/app/admin_user'
                    },
                    {
                        label: 'menu_admin_permissions',
                        id: 'permissions',
                        icon: 'fa-solid fa-ranking-star',
                        to: '/app/permissions'
                    }
                ]
            },
            {
                label: 'menu_errors',
                id: 'errors',
                icon: 'fa-solid fa-bug',
                to: '/app/errors'
            }
        ]
    }
]);

// Dynamický překlad menu (computed zajistí automatickou změnu při změně jazyka)
const translatedModel = computed(() => {
    const translateMenu = (items) => {
        if (!items) return [];
        return items.map((item) => ({
            ...item,
            label: item.label ? i18n.global.t(item.label) : '',
            items: item.items ? translateMenu(item.items) : undefined
        }));
    };
    return translateMenu(model.value);
});

// Filtrování podle oprávnění (používá `translatedModel`, ne `model`)
const filterMenu = (menu) => {
    return menu
        .map((item) => {
            const hasPermission = !user_data.userData.pages_with_permissions.includes(item.id) || user_data.page_premission(item.id);
            if (item.items) {
                item.items = filterMenu(item.items);
            }
            return hasPermission ? item : null;
        })
        .filter(Boolean);
};

// Použijeme filtrované a přeložené menu
const filteredMenuItems = computed(() => filterMenu(translatedModel.value));
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in filteredMenuItems" :key="item.id">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>
