import { defineStore } from 'pinia';
import { http, TOKEN_KEY } from '../api/http';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem(TOKEN_KEY),
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token),
    },
    actions: {
        async register(payload) {
            this.loading = true;
            this.error = null;
            try {
                await http.post('/register', payload);
            } finally {
                this.loading = false;
            }
        },
        async login(payload) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await http.post('/login', payload);
                this.token = data.token.access_token;
                localStorage.setItem(TOKEN_KEY, this.token);
                this.user = data.user;
                return data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async oauthPasswordGrant(payload) {
            const { data } = await http.post('/oauth/token', payload);
            this.token = data.access_token;
            localStorage.setItem(TOKEN_KEY, this.token);
            await this.me();
            return data;
        },
        async me() {
            if (!this.token) return null;
            const { data } = await http.get('/me');
            this.user = data;
            return data;
        },
        async logout() {
            try {
                if (this.token) {
                    await http.post('/logout');
                }
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem(TOKEN_KEY);
            }
        },
    },
});
