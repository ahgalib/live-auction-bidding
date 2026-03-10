<script setup>
defineProps({
    bids: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <section class="rounded-xl border border-white/20 bg-black/20 p-4">
        <p class="mb-3 text-xs uppercase tracking-[0.2em] text-white/70">Live Bid Feed</p>
        <ul class="space-y-2 text-sm">
            <li v-for="bid in bids" :key="bid.id" class="flex items-center justify-between rounded-md bg-white/5 px-3 py-2">
                <span>${{ Number(bid.amount).toFixed(2) }}</span>
                <span class="text-xs uppercase" :class="bid.loading ? 'text-amber-300' : 'text-cyan-300'">
                    {{ bid.loading ? 'pending' : bid.status }}
                </span>
            </li>
        </ul>
    </section>
</template>
