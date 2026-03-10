<script setup>
defineProps({
    price: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div class="rounded-xl border border-amber-300/40 bg-black/30 p-5">
        <p class="text-xs uppercase tracking-[0.2em] text-amber-200/70">Current Price</p>
        <Transition name="price-slide" mode="out-in">
            <h1 :key="price" class="mt-2 text-5xl font-bold text-amber-100">${{ price.toFixed(2) }}</h1>
        </Transition>
    </div>
</template>
