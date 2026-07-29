<template>
    <aside class="filter-panel">
        <div class="filter-panel__header">
            <div>
                <p>Contrôles</p>
                <h3 class="display-type">{{ title }}</h3>
            </div>
            <button @click="$emit('reset')">Réinitialiser</button>
        </div>
        <div class="space-y-4 p-5">
            <slot />
        </div>
    </aside>
</template>

<script setup>
defineProps({ title: { type: String, default: 'Filtres' } });
defineEmits(['reset']);
</script>

<style scoped>
.filter-panel {
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 19px;
    background: rgba(17, 21, 25, 0.88);
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18);
}
.filter-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 17px 20px;
    border-bottom: 1px solid var(--line);
}
.filter-panel__header p {
    margin: 0;
    color: var(--signal-bright);
    font-size: 0.55rem;
    font-weight: 720;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.filter-panel__header h3 {
    margin: 5px 0 0;
    font-size: 1rem;
    font-weight: 620;
}
.filter-panel__header button {
    border: 0;
    background: transparent;
    color: #697176;
    font-size: 0.67rem;
}
.filter-panel__header button:hover {
    color: var(--paper);
}
</style>
