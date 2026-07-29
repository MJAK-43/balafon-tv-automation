<template>
    <label class="search-field">
        <MagnifyingGlassIcon class="h-[18px] w-[18px]" />
        <input
            :value="modelValue"
            :placeholder="placeholder"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <span>⌘ K</span>
    </label>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Rechercher' },
});
defineEmits(['update:modelValue']);
</script>

<style scoped>
.search-field {
    display: flex;
    min-height: 46px;
    align-items: center;
    gap: 10px;
    padding: 0 13px;
    border: 1px solid var(--line);
    border-radius: 13px;
    background: rgba(8, 10, 12, 0.64);
    color: #60686d;
}
.search-field:focus-within {
    border-color: rgba(255, 77, 46, 0.6);
    box-shadow: 0 0 0 4px rgba(255, 77, 46, 0.08);
}
.search-field input {
    min-width: 0;
    flex: 1;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    color: var(--paper);
    font-size: 0.77rem;
    outline: 0;
}
.search-field span {
    padding: 4px 6px;
    border: 1px solid var(--line);
    border-radius: 6px;
    color: #4f575c;
    font-size: 0.55rem;
}
</style>
