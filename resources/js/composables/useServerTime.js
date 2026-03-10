import { computed, ref } from 'vue';
import { http } from '../api/http';

const offsetMs = ref(0);
const lastSyncedAt = ref(null);

export function useServerTime() {
    const now = computed(() => Date.now() + offsetMs.value);

    const sync = async () => {
        const response = await http.get('/server-time');
        const serverTime = new Date(response.data.server_time_utc).getTime();
        offsetMs.value = serverTime - Date.now();
        lastSyncedAt.value = new Date().toISOString();
    };

    return {
        now,
        offsetMs,
        lastSyncedAt,
        sync,
    };
}
