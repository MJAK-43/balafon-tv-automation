<template>
    <div :class="variantClass" class="rounded-[1.5rem] border p-4">
        <p class="text-sm font-semibold">{{ title }}</p>
        <p class="mt-1 text-sm opacity-90">{{ description }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    variant: { type: String, default: 'warning' },
});

const classes = {
    success: 'border-emerald-400/30 bg-emerald-500/10 text-emerald-100',
    warning: 'border-amber-400/30 bg-amber-500/10 text-amber-100',
    danger: 'border-rose-400/30 bg-rose-500/10 text-rose-100',
};

const variantClass = computed(() => classes[props.variant] ?? classes.warning);
</script>
