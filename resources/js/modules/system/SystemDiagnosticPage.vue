<template>
    <div class="mx-auto max-w-6xl px-4 py-6 lg:px-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-cyan-300">System</p>
                <h1 class="mt-2 text-3xl font-semibold">Diagnostic</h1>
            </div>
            <RouterLink class="rounded-2xl border border-white/10 px-4 py-3" to="/">Back to dashboard</RouterLink>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">Requirements</p>
                <pre class="mt-3 text-xs text-slate-200">{{ requirements }}</pre>
            </article>
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">Health</p>
                <pre class="mt-3 text-xs text-slate-200">{{ health }}</pre>
            </article>
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">vMix endpoints</p>
                <pre class="mt-3 text-xs text-slate-200">{{ vmix }}</pre>
            </article>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../../services/api';

const requirements = ref({});
const health = ref({});
const vmix = ref([]);

onMounted(async () => {
    const [requirementsResponse, healthResponse, vmixResponse] = await Promise.all([
        api.get('/system/requirements'),
        api.get('/system/health'),
        api.get('/system/vmix'),
    ]);

    requirements.value = requirementsResponse.data;
    health.value = healthResponse.data;
    vmix.value = vmixResponse.data;
});
</script>
