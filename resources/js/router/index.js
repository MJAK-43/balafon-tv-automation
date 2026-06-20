import { createRouter, createWebHistory } from 'vue-router';

import DashboardPage from '../modules/dashboard/DashboardPage.vue';
import LoginPage from '../modules/auth/LoginPage.vue';
import SystemDiagnosticPage from '../modules/system/SystemDiagnosticPage.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
    { path: '/', name: 'dashboard', component: DashboardPage, meta: { auth: true } },
    { path: '/system/diagnostic', name: 'system-diagnostic', component: SystemDiagnosticPage, meta: { auth: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (auth.booted === false) {
        await auth.bootstrap();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
