<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuctionStore } from '../stores/auctionStore';
import { useAuthStore } from '../stores/authStore';
import { useAuctionSubscription } from '../composables/useAuctionSubscription';
import AuctionCountdown from '../components/auction/AuctionCountdown.vue';
import BidActionPad from '../components/auction/BidActionPad.vue';
import LivePriceDisplay from '../components/auction/LivePriceDisplay.vue';
import ParticipationBadge from '../components/auction/ParticipationBadge.vue';
import BidFeed from '../components/auction/BidFeed.vue';
import ConnectionOverlay from '../components/shared/ConnectionOverlay.vue';
import ToastMessage from '../components/shared/ToastMessage.vue';

const props = defineProps({
    auctionId: {
        type: Number,
        required: true,
    },
});

const auction = useAuctionStore();
const auth = useAuthStore();
const toast = ref('');
const toastKind = ref('info');
const ariaStatus = ref('');

const loginForm = ref({
    email: '',
    password: '',
});

const registerForm = ref({
    name: '',
    email: '',
    password: '',
});

const oauthForm = ref({
    grant_type: 'password',
    client_id: 1,
    client_secret: 'dev-client-secret-change-me',
    username: '',
    password: '',
    scope: 'bid:write auction:read',
});

const showToast = (message, kind = 'info') => {
    toast.value = message;
    toastKind.value = kind;
    window.setTimeout(() => {
        toast.value = '';
    }, 2500);
};

const playTone = (kind) => {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    const ctx = new AudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = kind === 'success' ? 'triangle' : 'sawtooth';
    osc.frequency.value = kind === 'success' ? 880 : 220;
    gain.gain.value = 0.03;
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.12);
};

const handleBid = async (amount) => {
    try {
        await auction.placeBid(amount);
        playTone('success');
        showToast('Bid submitted');
    } catch (error) {
        playTone('error');
        const code = error.response?.data?.error_code;
        if (code === 'LOW_BID') {
            ariaStatus.value = 'You have been outbid';
            showToast('Bid too low. You were outbid.', 'error');
        } else if (error.response?.status === 401) {
            showToast('Please login first', 'error');
        } else {
            showToast('Bid failed', 'error');
        }
    }
};

const handleRegister = async () => {
    await auth.register(registerForm.value);
    showToast('Registration successful');
};

const handleLogin = async () => {
    await auth.login(loginForm.value);
    showToast('Logged in');
};

const handleOAuth = async () => {
    await auth.oauthPasswordGrant(oauthForm.value);
    showToast('OAuth token issued');
};

const connectionLost = computed(() => auction.connectionState === 'disconnected');

onMounted(async () => {
    await auction.bootstrap(props.auctionId);
    try {
        await auth.me();
    } catch {
        // No token session.
    }
});

useAuctionSubscription(auction);
</script>

<template>
    <main class="auction-shell">
        <ConnectionOverlay :visible="connectionLost" />

        <div class="mx-auto max-w-6xl p-4 md:p-8">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.25em] text-cyan-200/70">Velocity War Room</p>
                    <h1 class="text-3xl font-semibold text-white">Auction #{{ auction.auctionId }}</h1>
                </div>
                <ParticipationBadge :watcher-count="auction.watcherCount" :connection-state="auction.connectionState" />
            </header>

            <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
                <section class="space-y-4">
                    <LivePriceDisplay :price="auction.currentPrice" />
                    <AuctionCountdown :end-time="auction.endTime" />
                    <BidActionPad :current-price="auction.currentPrice" :disabled="auction.bidPending || !auth.isAuthenticated" @submit="handleBid" />
                    <p class="sr-only" aria-live="assertive">{{ ariaStatus }}</p>
                </section>

                <aside class="space-y-4">
                    <BidFeed :bids="auction.bids" />
                    <section class="rounded-xl border border-white/20 bg-white/5 p-4">
                        <h2 class="text-sm uppercase tracking-[0.2em] text-white/70">User Access</h2>
                        <div v-if="auth.isAuthenticated" class="mt-3 text-sm">
                            <p class="text-green-300">Logged in as {{ auth.user?.email }}</p>
                            <button class="mt-2 rounded-md bg-white/10 px-3 py-2" @click="auth.logout">Logout</button>
                        </div>
                        <div v-else class="mt-3 space-y-3">
                            <div class="grid gap-2">
                                <input v-model="registerForm.name" class="auth-input" placeholder="Name" />
                                <input v-model="registerForm.email" class="auth-input" placeholder="Email" />
                                <input v-model="registerForm.password" class="auth-input" type="password" placeholder="Password" />
                                <button class="auth-btn" @click="handleRegister">Register</button>
                            </div>
                            <div class="grid gap-2">
                                <input v-model="loginForm.email" class="auth-input" placeholder="Email" />
                                <input v-model="loginForm.password" class="auth-input" type="password" placeholder="Password" />
                                <button class="auth-btn" @click="handleLogin">Login</button>
                            </div>
                            <div class="grid gap-2">
                                <input v-model="oauthForm.username" class="auth-input" placeholder="OAuth Username" />
                                <input v-model="oauthForm.password" class="auth-input" type="password" placeholder="OAuth Password" />
                                <button class="auth-btn" @click="handleOAuth">OAuth2 Password Grant</button>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>

        <ToastMessage :message="toast" :kind="toastKind" />
    </main>
</template>
