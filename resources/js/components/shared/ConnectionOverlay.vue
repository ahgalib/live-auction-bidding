<script setup>
defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div v-if="visible" class="pointer-events-none absolute inset-0 z-50 grid place-items-center bg-black/70 backdrop-blur-sm">
        <div class="rounded-xl border border-red-300/40 bg-red-500/10 px-6 py-4 text-center">
            <p class="text-sm uppercase tracking-[0.2em] text-red-200">Connection Lost</p>
            <p class="mt-2 text-red-50">Trying to reconnect and resync state...</p>
        </div>
    </div>
</template>
