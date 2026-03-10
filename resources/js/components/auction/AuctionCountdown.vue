<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { intervalToDuration } from 'date-fns';
import { useServerTime } from '../../composables/useServerTime';

const props = defineProps({
    endTime: {
        type: String,
        default: null,
    },
});

const { now } = useServerTime();
const remainingMs = ref(0);
let rafId = null;
let lastTick = 0;

const tick = (t) => {
    if (t - lastTick >= 10) {
        lastTick = t;
        const target = props.endTime ? new Date(props.endTime).getTime() : 0;
        remainingMs.value = Math.max(0, target - now.value);
    }
    rafId = requestAnimationFrame(tick);
};

onMounted(() => {
    rafId = requestAnimationFrame(tick);
});

onUnmounted(() => {
    if (rafId) cancelAnimationFrame(rafId);
});

const timeLabel = computed(() => {
    const duration = intervalToDuration({ start: 0, end: remainingMs.value });
    const hh = String(duration.hours ?? 0).padStart(2, '0');
    const mm = String(duration.minutes ?? 0).padStart(2, '0');
    const ss = String(duration.seconds ?? 0).padStart(2, '0');
    const cs = String(Math.floor((remainingMs.value % 1000) / 10)).padStart(2, '0');
    return `${hh}:${mm}:${ss}:${cs}`;
});
</script>

<template>
    <div class="rounded-xl border border-cyan-300/40 bg-cyan-500/10 p-4">
        <p class="text-xs uppercase tracking-[0.2em] text-cyan-200/80">Auction Timer</p>
        <p class="mt-2 font-mono text-3xl font-semibold text-cyan-100">{{ timeLabel }}</p>
    </div>
</template>
