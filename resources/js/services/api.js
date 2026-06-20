import axios from 'axios';

export const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = window.localStorage.getItem('balafon_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});
