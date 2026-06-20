<template>
    <div class="mx-auto flex min-h-screen max-w-7xl gap-6 px-4 py-6 lg:px-6">
        <aside class="hidden w-72 shrink-0 rounded-3xl border border-white/10 bg-white/5 p-5 lg:block">
            <p class="text-xs uppercase tracking-[0.35em] text-cyan-300">Balafon</p>
            <h1 class="mt-3 text-2xl font-semibold">Phase 0 Console</h1>
            <nav class="mt-8 space-y-2 text-sm">
                <RouterLink class="block rounded-2xl px-4 py-3 hover:bg-white/10" to="/">Dashboard</RouterLink>
                <RouterLink class="block rounded-2xl px-4 py-3 hover:bg-white/10" to="/system/diagnostic">System Diagnostic</RouterLink>
            </nav>
            <button class="mt-8 rounded-2xl border border-white/10 px-4 py-3 text-sm" @click="logout">Logout</button>
        </aside>

        <main class="flex-1 space-y-6">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-emerald-300">Realtime</p>
                        <h2 class="mt-2 text-3xl font-semibold">Foundation dashboard</h2>
                    </div>
                    <div class="flex gap-3">
                        <button class="rounded-2xl bg-cyan-400 px-4 py-3 font-semibold text-slate-950" @click="runDiagnostic">Run Diagnostic</button>
                        <button class="rounded-2xl border border-white/10 px-4 py-3" @click="playTest">Play Test</button>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                    <p class="text-sm text-slate-400">OS</p>
                    <p class="mt-3 text-xl font-semibold">{{ systemLabel }}</p>
                </article>
                <article class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                    <p class="text-sm text-slate-400">vMix</p>
                    <p class="mt-3 text-xl font-semibold">{{ summary?.vmix?.connected ? 'Connected' : 'Offline' }}</p>
                </article>
                <article class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                    <p class="text-sm text-slate-400">Version</p>
                    <p class="mt-3 text-xl font-semibold">{{ summary?.vmix?.version ?? 'n/a' }}</p>
                </article>
                <article class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                    <p class="text-sm text-slate-400">Inputs</p>
                    <p class="mt-3 text-xl font-semibold">{{ summary?.vmix?.inputs_count ?? 0 }}</p>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-[1fr_1fr]">
                <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold">vMix connection</h3>
                        <button class="rounded-2xl border border-white/10 px-4 py-2 text-sm" @click="testConnection">Test connection</button>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm">
                            <span class="text-slate-400">Name</span>
                            <input v-model="connectionForm.name" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 outline-none">
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="text-slate-400">Host</span>
                            <input v-model="connectionForm.host" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 outline-none">
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="text-slate-400">Port</span>
                            <input v-model.number="connectionForm.port" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 outline-none" type="number">
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="text-slate-400">Timeout ms</span>
                            <input v-model.number="connectionForm.timeout_ms" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 outline-none" type="number">
                        </label>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button class="rounded-2xl bg-cyan-400 px-4 py-3 font-semibold text-slate-950" @click="saveConnection">Save connection</button>
                        <span class="rounded-2xl border border-white/10 px-4 py-3 text-sm text-slate-300">Default Docker host: `host.docker.internal`</span>
                    </div>
                    <p v-if="connectionMessage" class="mt-4 text-sm text-emerald-300">{{ connectionMessage }}</p>
                    <p v-if="summary?.vmix?.error" class="mt-2 text-sm text-rose-300">{{ summary.vmix.error }}</p>
                </article>

                <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-xl font-semibold">vMix live state</h3>
                    <div class="mt-5 grid gap-3 text-sm md:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">Edition: {{ summary?.vmix?.edition ?? 'n/a' }}</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">Active: {{ summary?.vmix?.active_input ?? 'n/a' }}</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">Preview: {{ summary?.vmix?.preview_input ?? 'n/a' }}</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">Streaming: {{ summary?.vmix?.streaming ? 'On' : 'Off' }}</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">Recording: {{ summary?.vmix?.recording ? 'On' : 'Off' }}</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">External: {{ summary?.vmix?.external ? 'On' : 'Off' }}</div>
                    </div>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.25fr_0.75fr]">
                <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold">Latest audits</h3>
                        <span class="text-xs uppercase tracking-[0.3em] text-slate-400">{{ summary?.latest_audits?.length ?? 0 }} events</span>
                    </div>
                    <div class="mt-5 space-y-3">
                        <div v-for="item in summary?.latest_audits ?? []" :key="item.uuid" class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                            <p class="font-medium">{{ item.action }}</p>
                            <p class="mt-1 text-sm text-slate-400">{{ item.entity_type }} · {{ item.created_at }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-xl font-semibold">Last vMix tests</h3>
                    <div class="mt-5 space-y-3">
                        <div v-for="item in summary?.latest_vmix_tests ?? []" :key="item.uuid" class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                            <p class="font-medium">{{ item.command_name }}</p>
                            <p class="mt-1 text-sm text-slate-400">{{ item.status }} · {{ item.executed_at }}</p>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../../services/api';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const summary = ref(null);
const connectionMessage = ref('');
const connectionForm = reactive({
    uuid: null,
    name: 'vMix local',
    host: 'host.docker.internal',
    port: 8088,
    timeout_ms: 3000,
    is_active: true,
});
let timer = null;

const systemLabel = computed(() => {
    const system = summary.value?.system;
    return system ? `${system.os_name} ${system.os_version ?? ''}`.trim() : 'n/a';
});

const fetchSummary = async () => {
    const { data } = await api.get('/dashboard/summary');
    summary.value = data;
    const firstConnection = data?.connections?.[0];

    if (firstConnection) {
        connectionForm.uuid = firstConnection.uuid;
        connectionForm.name = firstConnection.name;
        connectionForm.host = firstConnection.host;
        connectionForm.port = firstConnection.port;
        connectionForm.timeout_ms = firstConnection.timeout_ms;
        connectionForm.is_active = firstConnection.is_active;
    }
};

const runDiagnostic = async () => {
    await api.post('/system/run-diagnostic');
    await fetchSummary();
};

const playTest = async () => {
    await api.post('/vmix/play-test');
    await fetchSummary();
};

const saveConnection = async () => {
    connectionMessage.value = '';
    await api.put(`/vmix/connections/${connectionForm.uuid}`, {
        name: connectionForm.name,
        host: connectionForm.host,
        port: connectionForm.port,
        timeout_ms: connectionForm.timeout_ms,
        is_active: connectionForm.is_active,
    });
    connectionMessage.value = 'Connection updated.';
    await fetchSummary();
};

const testConnection = async () => {
    connectionMessage.value = '';
    await api.post(`/vmix/connections/${connectionForm.uuid}/test`);
    connectionMessage.value = 'Connection test completed.';
    await fetchSummary();
};

const logout = async () => {
    await auth.logout();
    await router.push({ name: 'login' });
};

onMounted(async () => {
    await fetchSummary();
    timer = window.setInterval(fetchSummary, 30000);
});

onBeforeUnmount(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>
