<template>
    <div class="space-y-3">
        <div v-for="item in items" :key="item.id ?? item.uuid ?? item.message" class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4">
            <p class="text-sm font-medium text-white">{{ item.title ?? item.action ?? item.message }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ item.subtitle ?? item.created_at ?? item.meta }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    items: { type: Array, default: () => [] },
});
</script>
