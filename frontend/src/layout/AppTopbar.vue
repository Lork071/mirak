<script setup>
import LanguageConfigurator from '@/components/LanguageConfigurator.vue';
import config from '@/config';
import { useLayout } from '@/layout/composables/layout';
import { useApi } from '@/service/api';
import AppConfigurator from './AppConfigurator.vue';

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();

const { api_post } = useApi();
async function logout() {
    const response = await api_post(config.endpoint_login, { method: 'logout' });
}
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="onMenuToggle">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/" class="layout-topbar-logo">
                <svg width="60" height="54" id="mirak_logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600">
                    <rect style="fill: var(--primary-color)" class="cls-1" x="190.92" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-115.96 167.22) rotate(-30)" />
                    <rect style="fill: var(--primary-color)" class="cls-1" x="373.29" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-91.53 258.4) rotate(-30)" />
                    <circle style="fill: var(--primary-color)" class="cls-1" cx="117.98" cy="378.56" r="62.54" />
                </svg>

                <span class="intro-page-navbar-left-title">{{ $t('mirak_plus') }}</span>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <div class="relative">
                    <AppConfigurator />
                    <LanguageConfigurator />
                </div>
            </div>
            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <button type="button" class="layout-topbar-action">
                        <i class="pi pi-calendar"></i>
                        <span>Calendar</span>
                    </button>
                    <button type="button" class="layout-topbar-action">
                        <i class="pi pi-inbox"></i>
                        <span>Messages</span>
                    </button>
                    <button type="button" class="layout-topbar-action" @click="logout">
                        <i class="pi pi-user"></i>
                        <span>Profile</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
