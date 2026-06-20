<template>
    <div class="flex min-h-screen items-center justify-center px-6">
        <form class="w-full max-w-md space-y-6 rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur" @submit.prevent="submit">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-cyan-300">Balafon</p>
                <h1 class="mt-3 text-3xl font-semibold">Broadcast Manager</h1>
                <p class="mt-2 text-sm text-slate-300">Foundation access for Phase 0 diagnostics and vMix validation.</p>
            </div>

            <div class="space-y-4">
                <input v-model="form.email" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 outline-none" placeholder="Email" type="email">
                <input v-model="form.password" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 outline-none" placeholder="Password" type="password">
            </div>

            <button class="w-full rounded-2xl bg-cyan-400 px-4 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300" type="submit">
                Sign in
            </button>

            <p v-if="error" class="text-sm text-rose-300">{{ error }}</p>
            <p class="text-xs text-slate-400">Default seed: `admin@balafon.local` / `password`</p>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const error = ref('');
const form = reactive({
    email: 'admin@balafon.local',
    password: 'password',
});

const submit = async () => {
    error.value = '';

    try {
        await auth.login(form);
        await router.push({ name: 'dashboard' });
    } catch (err) {
        error.value = err?.response?.data?.message ?? 'Authentication failed.';
    }
};
</script>
