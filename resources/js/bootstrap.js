import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

if (import.meta.env.VITE_PUSHER_APP_KEY) {
    const pusherConfig = {
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    };

    if (import.meta.env.VITE_PUSHER_HOST) {
        pusherConfig.wsHost = import.meta.env.VITE_PUSHER_HOST;
        pusherConfig.wsPort = Number(import.meta.env.VITE_PUSHER_PORT || 80);
        pusherConfig.wssPort = Number(import.meta.env.VITE_PUSHER_PORT || 443);
    }

    window.Echo = new Echo(pusherConfig);
}
