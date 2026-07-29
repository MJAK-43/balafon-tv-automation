import { defineStore } from 'pinia';
import { api } from '../services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: window.localStorage.getItem('balafon_token'),
        booted: false,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),
    },
    actions: {
        async bootstrap() {
            if (!this.token) {
                this.booted = true;
                return;
            }

            try {
                const { data } = await api.get('/auth/me', {
                    timeout: 5000,
                });
                this.user = data;
            } catch (error) {
                this.token = null;
                this.user = null;
                window.localStorage.removeItem('balafon_token');
            } finally {
                this.booted = true;
            }
        },
        async login(payload) {
            const { data } = await api.post('/auth/login', payload);
            this.token = data.token;
            this.user = data.user;
            window.localStorage.setItem('balafon_token', data.token);
        },
        async logout() {
            try {
                await api.post('/auth/logout');
            } finally {
                this.token = null;
                this.user = null;
                window.localStorage.removeItem('balafon_token');
            }
        },
    },
});
