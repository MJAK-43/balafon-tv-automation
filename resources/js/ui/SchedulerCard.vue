<template>
    <article class="scheduler-card">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[#f4f0e7]">{{ title }}</p>
                <p class="mt-1 truncate text-[10px] font-semibold uppercase tracking-[0.18em] text-[#636b70]">{{ meta }}</p>
            </div>
            <slot name="badge" />
        </div>
        <div class="mt-3">
            <slot />
        </div>
    </article>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    meta: { type: String, default: '' },
});
</script>

<style scoped>
.scheduler-card {
    border: 1px solid var(--line);
    border-radius: 15px;
    padding: 15px;
    background: rgba(8, 10, 12, 0.46);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.018);
}
</style>
