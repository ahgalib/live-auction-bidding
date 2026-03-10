<script setup>
import { ref } from 'vue';

const props = defineProps({
    currentPrice: {
        type: Number,
        default: 0,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submit']);
const amount = ref('');

const quickBid = (delta) => {
    amount.value = (props.currentPrice + delta).toFixed(2);
    submit();
};

const submit = () => {
    const parsed = Number(amount.value);
    if (!Number.isFinite(parsed) || parsed <= 0) return;
    emit('submit', parsed);
};
</script>

<template>
    <section class="rounded-xl border border-rose-300/40 bg-rose-500/10 p-5">
        <p class="text-xs uppercase tracking-[0.2em] text-rose-200/80">Bid Action Pad</p>
        <div class="mt-4 flex gap-2">
            <input
                v-model="amount"
                type="number"
                step="0.01"
                min="0"
                class="h-12 w-full rounded-lg border border-white/20 bg-black/40 px-3 text-lg"
                placeholder="Enter bid amount"
                :disabled="disabled"
            />
            <button class="h-12 rounded-lg bg-rose-500 px-5 font-semibold disabled:opacity-50" :disabled="disabled" @click="submit">
                Bid
            </button>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <button class="quick-bid" :disabled="disabled" @click="quickBid(10)">+ $10</button>
            <button class="quick-bid" :disabled="disabled" @click="quickBid(50)">+ $50</button>
            <button class="quick-bid" :disabled="disabled" @click="quickBid(100)">+ $100</button>
            <button class="quick-bid" :disabled="disabled" @click="quickBid(250)">+ $250</button>
        </div>
    </section>
</template>
