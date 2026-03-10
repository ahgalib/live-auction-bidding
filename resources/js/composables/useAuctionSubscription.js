import { onMounted, onUnmounted } from 'vue';

export function useAuctionSubscription(store) {
    let channel = null;
    let flushTimer = null;
    let bidsBuffer = [];

    const flush = () => {
        if (bidsBuffer.length) {
            store.flushBufferedBids(bidsBuffer);
            bidsBuffer = [];
        }
    };

    const onBidPlaced = (payload) => {
        store.currentPrice = Number(payload.amount ?? store.currentPrice);
        store.endTime = payload.endTime ?? store.endTime;

        if (payload.bid) {
            bidsBuffer.push(payload.bid);
        } else {
            bidsBuffer.push({
                id: `evt-${Date.now()}`,
                amount: Number(payload.amount ?? store.currentPrice),
                createdAt: new Date().toISOString(),
                status: 'accepted',
            });
        }
    };

    const subscribe = () => {
        if (window.Echo && store.auctionId) {
            channel = window.Echo.private(`auction.${store.auctionId}`);
            channel.listen('.BidPlaced', onBidPlaced);

            window.Echo.connector?.pusher?.connection?.bind('connected', () => {
                store.connectionState = 'connected';
            });

            window.Echo.connector?.pusher?.connection?.bind('disconnected', () => {
                store.connectionState = 'disconnected';
            });
        }
    };

    const unsubscribe = () => {
        if (channel && window.Echo) {
            window.Echo.leave(`private-auction.${store.auctionId}`);
        }
        channel = null;
    };

    const onVisibilityChange = () => {
        if (document.visibilityState === 'visible') {
            store.hardResync();
        }
    };

    onMounted(() => {
        subscribe();
        flushTimer = window.setInterval(flush, 200);
        document.addEventListener('visibilitychange', onVisibilityChange);
    });

    onUnmounted(() => {
        if (flushTimer) {
            window.clearInterval(flushTimer);
        }
        unsubscribe();
        document.removeEventListener('visibilitychange', onVisibilityChange);
    });
}
