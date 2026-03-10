import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { provideApolloClient } from '@vue/apollo-composable';
import AuctionRoom from './views/AuctionRoom.vue';
import { apolloClient } from './api/apolloClient';

const mountEl = document.getElementById('auction-app');

if (mountEl) {
    provideApolloClient(apolloClient);

    const app = createApp(AuctionRoom, {
        auctionId: Number(mountEl.dataset.auctionId || 1),
    });

    app.use(createPinia());
    app.mount(mountEl);
}
