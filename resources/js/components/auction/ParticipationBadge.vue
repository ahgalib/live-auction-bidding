<script setup>
defineProps({
    watcherCount: {
        type: Number,
        default: 0,
    },
    connectionState: {
        type: String,
        default: 'connecting',
    },
});
</script>

<template>
    <div class="inline-flex items-center gap-2 rounded-full border border-lime-300/40 bg-lime-300/10 px-3 py-1 text-sm">
        <span class="h-2 w-2 rounded-full" :class="connectionState === 'connected' ? 'bg-green-400' : 'bg-orange-400'" />
        <span>{{ watcherCount }} Live Bidders</span>
    </div>
</template>
