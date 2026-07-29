<template>
    <article class="stat-tile">
        <div class="stat-tile__top">
            <span>{{ label }}</span>
            <i />
        </div>
        <strong class="display-type">{{ value }}</strong>
        <p v-if="hint">{{ hint }}</p>
    </article>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
});
</script>

<style scoped>
.stat-tile {
    position: relative;
    min-height: 152px;
    overflow: hidden;
    padding: 20px;
    border: 1px solid var(--line);
    border-radius: 19px;
    background: rgba(17, 21, 25, 0.88);
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18);
}

.stat-tile::after {
    position: absolute;
    right: -30px;
    bottom: -48px;
    width: 110px;
    height: 110px;
    border: 1px solid rgba(255, 255, 255, 0.04);
    border-radius: 50%;
    content: '';
}

.stat-tile__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #7d858a;
    font-size: 0.68rem;
    font-weight: 620;
}

.stat-tile__top i {
    width: 16px;
    height: 2px;
    border-radius: 2px;
    background: var(--signal);
}

.stat-tile strong {
    display: block;
    max-width: 100%;
    margin-top: 22px;
    overflow: hidden;
    color: var(--paper);
    font-size: clamp(1.65rem, 3vw, 2.4rem);
    font-weight: 620;
    letter-spacing: -0.045em;
    line-height: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.stat-tile p {
    margin: 13px 0 0;
    color: #545c61;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
</style>
