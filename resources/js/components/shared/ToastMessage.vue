<script setup>
defineProps({
    message: {
        type: String,
        default: '',
    },
    kind: {
        type: String,
        default: 'info',
    },
});
</script>

<template>
    <div
        v-if="message"
        class="fixed bottom-4 right-4 z-50 rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="kind === 'error' ? 'bg-red-500 text-white' : 'bg-cyan-500 text-black'"
    >
        {{ message }}
    </div>
</template>
