<template>
    <Dialog v-model:visible="visibleProxy" modal :closable="false" :style="{ width: '30rem' }">
        <template #header>
            <div>
                <p class="text-sm font-semibold text-slate-900">{{ title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ description }}</p>
            </div>
        </template>

        <div class="flex justify-end gap-3 pt-3">
            <button class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600" @click="visibleProxy = false">Cancel</button>
            <button class="rounded-full bg-rose-500 px-4 py-2 text-sm font-medium text-white" @click="$emit('confirm')">Confirm</button>
        </div>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
    visible: { type: Boolean, default: false },
    title: { type: String, required: true },
    description: { type: String, required: true },
});

const emit = defineEmits(['update:visible', 'confirm']);

const visibleProxy = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});
</script>
