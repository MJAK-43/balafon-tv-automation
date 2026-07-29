<template>
    <button :class="{ 'playlist-card--active': active }" class="playlist-card">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[#f4f0e7]">{{ title }}</p>
                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#626a6f]">{{ subtitle }}</p>
            </div>
            <slot name="badge" />
        </div>
        <p v-if="description" class="mt-3 line-clamp-2 text-xs leading-5 text-[#858d92]">{{ description }}</p>
        <div class="mt-4 flex items-center justify-between border-t border-white/[0.07] pt-3 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#596166]">
            <span>{{ itemsLabel }}</span>
            <span>{{ durationLabel }}</span>
        </div>
    </button>
</template>

<script setup>
defineProps({
    active: { type: Boolean, default: false },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    description: { type: String, default: '' },
    itemsLabel: { type: String, default: '' },
    durationLabel: { type: String, default: '' },
});
</script>

<style scoped>
.playlist-card {
    width: 100%;
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: rgba(8, 10, 12, 0.42);
    text-align: left;
    transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
}
.playlist-card:hover {
    border-color: var(--line-strong);
    background: rgba(255, 255, 255, 0.025);
    transform: translateX(2px);
}
.playlist-card--active {
    border-color: rgba(255, 77, 46, 0.38);
    background: linear-gradient(90deg, rgba(255, 77, 46, 0.13), rgba(255, 77, 46, 0.025));
    box-shadow: inset 3px 0 0 var(--signal);
}
</style>
