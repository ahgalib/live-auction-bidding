import { defineStore } from 'pinia';
import { http } from '../api/http';
import { useServerTime } from '../composables/useServerTime';

export const useAuctionStore = defineStore('auction', {
    state: () => ({
        auctionId: null,
        currentPrice: 0,
        endTime: null,
        bids: [],
        watcherCount: 0,
        isActive: false,
        bidPending: false,
        connectionState: 'connecting',
        loading: true,
    }),
    actions: {
        async bootstrap(auctionId) {
            this.auctionId = auctionId;
            await Promise.all([this.fetchAuction(), useServerTime().sync()]);
            this.loading = false;
        },
        async fetchAuction() {
            if (!this.auctionId) return;
            const { data } = await http.get(`/auctions/${this.auctionId}`);
            this.currentPrice = Number(data.current_price ?? 0);
            this.endTime = data.end_time;
            this.isActive = data.status === 'active';
        },
        async placeBid(amount) {
            if (this.bidPending) return null;
            this.bidPending = true;

            const tempId = `tmp-${Date.now()}`;
            this.bids.unshift({
                id: tempId,
                amount,
                createdAt: new Date().toISOString(),
                status: 'pending',
                loading: true,
            });

            try {
                const { data } = await http.post(`/auctions/${this.auctionId}/bid`, { amount });
                this.currentPrice = Number(data.current_price ?? this.currentPrice);
                this.endTime = data.end_time ?? this.endTime;
                this.bids = this.bids.map((bid) => (bid.id === tempId ? { ...bid, loading: false, status: 'accepted' } : bid));
                return data;
            } catch (error) {
                this.bids = this.bids.filter((bid) => bid.id !== tempId);
                throw error;
            } finally {
                this.bidPending = false;
            }
        },
        flushBufferedBids(incomingBids) {
            const normalized = incomingBids.map((bid) => ({
                id: bid.id ?? `evt-${Date.now()}-${Math.random()}`,
                amount: Number(bid.amount ?? this.currentPrice),
                createdAt: bid.createdAt ?? new Date().toISOString(),
                status: bid.status ?? 'accepted',
                loading: false,
            }));

            this.bids.unshift(...normalized);
            this.bids = this.bids.slice(0, 100);
        },
        async hardResync() {
            await Promise.all([this.fetchAuction(), useServerTime().sync()]);
        },
    },
});
