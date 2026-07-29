import { createRouter, createWebHistory } from 'vue-router';

import DiffusionPage from '../modules/automation/DiffusionPage.vue';
import BrandingPage from '../modules/branding/BrandingPage.vue';
import ChannelsPage from '../modules/channel/ChannelsPage.vue';
import DashboardPage from '../modules/dashboard/DashboardPage.vue';
import LoginPage from '../modules/auth/LoginPage.vue';
import MediaPage from '../modules/media/MediaPage.vue';
import PlaylistPage from '../modules/playlist/PlaylistPage.vue';
import SchedulingPage from '../modules/scheduling/SchedulingPage.vue';
import SystemDiagnosticPage from '../modules/system/SystemDiagnosticPage.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
    { path: '/', name: 'dashboard', component: DashboardPage, meta: { auth: true } },
    { path: '/channels', name: 'channels', component: ChannelsPage, meta: { auth: true } },
    { path: '/diffusion', name: 'diffusion', component: DiffusionPage, meta: { auth: true } },
    { path: '/media', name: 'media', component: MediaPage, meta: { auth: true } },
    { path: '/habillages', name: 'branding', component: BrandingPage, meta: { auth: true } },
    { path: '/playlists', name: 'playlists', component: PlaylistPage, meta: { auth: true } },
    { path: '/planifications', alias: ['/scheduling'], name: 'scheduling', component: SchedulingPage, meta: { auth: true } },
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
