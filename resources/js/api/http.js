import axios from 'axios';

const TOKEN_KEY = 'auction_access_token';

export const http = axios.create({
    baseURL: '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});

http.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

export { TOKEN_KEY };
