<template>
    <span :class="badgeClass" class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium tracking-[0.18em] uppercase">
        <span class="h-2 w-2 rounded-full" :class="dotClass" />
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
});

const palette = {
    READY: ['border-emerald-400/30 bg-emerald-500/10 text-emerald-200', 'bg-emerald-400'],
    ARCHIVED: ['border-slate-500/30 bg-slate-500/10 text-slate-300', 'bg-slate-400'],
    DRAFT: ['border-amber-400/30 bg-amber-500/10 text-amber-200', 'bg-amber-400'],
    PENDING: ['border-slate-400/30 bg-slate-500/10 text-slate-200', 'bg-slate-300'],
    PREPARING: ['border-sky-400/30 bg-sky-500/10 text-sky-200', 'bg-sky-400'],
    SCHEDULED: ['border-sky-400/30 bg-sky-500/10 text-sky-200', 'bg-sky-400'],
    ON_AIR: ['border-orange-400/30 bg-orange-500/10 text-orange-200', 'bg-orange-400'],
    COMPLETED: ['border-emerald-400/30 bg-emerald-500/10 text-emerald-200', 'bg-emerald-400'],
    FAILED: ['border-rose-400/30 bg-rose-500/10 text-rose-200', 'bg-rose-400'],
    SKIPPED: ['border-slate-500/30 bg-slate-500/10 text-slate-300', 'bg-slate-400'],
};

const label = computed(() => props.status.replaceAll('_', ' '));
const badgeClass = computed(() => palette[props.status]?.[0] ?? 'border-white/10 bg-white/5 text-slate-200');
const dotClass = computed(() => palette[props.status]?.[1] ?? 'bg-slate-300');
</script>
