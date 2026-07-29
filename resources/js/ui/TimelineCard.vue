<template>
    <section class="timeline-panel">
        <header class="timeline-panel__header">
            <div>
                <p>{{ eyebrow }}</p>
                <h2 class="display-type">{{ title }}</h2>
            </div>
            <div class="timeline-panel__actions">
                <slot name="actions" />
            </div>
        </header>
        <div class="timeline-panel__body">
            <slot />
        </div>
    </section>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: 'Timeline' },
    title: { type: String, required: true },
});
</script>

<style scoped>
.timeline-panel {
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 21px;
    background: rgba(17, 21, 25, 0.88);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.2);
}

.timeline-panel__header {
    display: flex;
    min-height: 80px;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--line);
}

.timeline-panel__header p {
    margin: 0;
    color: var(--signal-bright);
    font-size: 0.58rem;
    font-weight: 720;
    letter-spacing: 0.21em;
    text-transform: uppercase;
}

.timeline-panel__header h2 {
    margin: 6px 0 0;
    color: var(--paper);
    font-size: 1.18rem;
    font-weight: 610;
    letter-spacing: -0.025em;
}

.timeline-panel__actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.timeline-panel__body {
    padding: 20px;
}
</style>
