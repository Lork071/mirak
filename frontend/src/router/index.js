import AppLayout from '@/layout/AppLayout.vue';
import { useUserStore } from '@/service/user';
import { createRouter, createWebHistory } from 'vue-router';

const baseURL = import.meta.env.VITE_BASE_URL || '/';

const pages_without_login = ['/auth/login', '/auth/login-callback-facebook', '/auth/login-callback', '/', '/auth/register', '/ticket_food', '/draft_intro', '/ticket', '/not_found', '/auth/forgot-password', '/volunteer'];

const router = createRouter({
    history: createWebHistory(baseURL),
    routes: [
        {
            path: '/',
            name: 'intro',
            component: () => import('@/views/Intro.vue')
        },
        {
            path: '/draft_intro',
            name: 'draft_intro',
            component: () => import('@/views/draft_intro.vue')
        },
        {
            path: '/app',
            component: AppLayout,
            children: [
                {
                    path: '/app',
                    name: 'dashboard',
                    component: () => import('@/views/app/Dashboard.vue')
                },
                {
                    path: '/app/admin_user',
                    name: 'adminUser',
                    component: () => import('@/views/app/admin/admin_user.vue')
                },
                {
                    path: '/app/user_all',
                    name: 'allUser',
                    component: () => import('@/views/app/admin/user_all.vue')
                },
                {
                    path: '/app/errors',
                    name: 'errors',
                    component: () => import('@/views/app/admin/errors.vue')
                },
                {
                    path: '/app/permissions',
                    name: 'permissions',
                    component: () => import('@/views/app/admin/permissions.vue')
                },
                {
                    path: '/app/qrscanner',
                    name: 'qr_scanner',
                    component: () => import('@/views/app/admin/qrscanner.vue')
                },
                {
                    path: '/app/all_participant',
                    name: 'all_participant',
                    component: () => import('@/views/app/admin/all_participant.vue')
                },
                {
                    path: '/app/participant',
                    name: 'participant',
                    component: () => import('@/views/app/admin/participant.vue')
                },
                {
                    path: '/app/mirak-crew',
                    name: 'all_mirak_crew',
                    component: () => import('@/views/app/admin/all_mirak_crew.vue')
                },
                {
                    path: '/app/mirak-crew-person',
                    name: 'mirak_crew_person',
                    component: () => import('@/views/app/admin/mirak_crew_person.vue')
                }
            ]
        },
        {
            path: '/',
            name: 'intro',
            component: () => import('@/views/Intro.vue')
        },
        {
            path: '/auth/login',
            name: 'login',
            component: () => import('@/views/pages/auth/Login.vue')
        },
        {
            path: '/auth/login-callback',
            name: 'login-callback',
            component: () => import('@/views/pages/auth/Login_callback.vue')
        },
        {
            path: '/auth/register',
            name: 'register',
            component: () => import('@/views/pages/auth/Register.vue')
        },
        {
            path: '/auth/forgot-password',
            name: 'forgot-password',
            component: () => import('@/views/pages/auth/forgot_pass.vue')
        },
        {
            path: '/auth/access',
            name: 'accessDenied',
            component: () => import('@/views/pages/auth/Access.vue')
        },
        {
            path: '/auth/error',
            name: 'error',
            component: () => import('@/views/pages/auth/Error.vue')
        },
        {
            path: '/get-ticket',
            name: 'get_ticket',
            component: () => import('@/views/pages/get_ticket.vue')
        },
        {
            path: '/mirak-crew',
            name: 'mirak-crew',
            component: () => import('@/views/pages/mirak_crew.vue')
        },
        {
            path: '/mirak-crew/success',
            name: 'mirak-crew-success',
            component: () => import('@/views/pages/mirak_crew_success.vue')
        },
        {
            path: '/ticket',
            name: 'ticket',
            component: () => import('@/views/pages/ticket.vue')
        },
        {
            path: '/not_found',
            name: 'not_found',
            component: () => import('@/views/pages/NotFound.vue')
        }
    ]
});

router.beforeEach(async (to, from, next) => {
    if (!pages_without_login.includes(to.path)) {
        const userStore = useUserStore();
        await userStore.fetchUserData(to.path);
    }
    next();
});

export default router;
