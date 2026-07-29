<template>
    <article class="asset-card">
        <div class="asset-card__head">
            <div class="min-w-0">
                <p>{{ eyebrow }}</p>
                <h3 class="display-type">{{ title }}</h3>
                <span>{{ description }}</span>
            </div>
            <slot name="status" />
        </div>
        <div class="asset-card__body">
            <slot />
        </div>
    </article>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: 'Asset' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<style scoped>
.asset-card {
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 19px;
    background: rgba(17, 21, 25, 0.88);
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18);
    transition: transform 180ms ease, border-color 180ms ease;
}
.asset-card:hover {
    border-color: rgba(255, 77, 46, 0.25);
    transform: translateY(-2px);
}
.asset-card__head {
    display: flex;
    min-height: 126px;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    border-bottom: 1px solid var(--line);
}
.asset-card__head p {
    margin: 0;
    color: var(--signal-bright);
    font-size: 0.58rem;
    font-weight: 720;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.asset-card__head h3 {
    margin: 9px 0 0;
    color: var(--paper);
    font-size: 1.12rem;
    font-weight: 620;
    letter-spacing: -0.025em;
}
.asset-card__head span {
    display: -webkit-box;
    margin-top: 8px;
    overflow: hidden;
    color: #7f878c;
    font-size: 0.75rem;
    line-height: 1.55;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.asset-card__body {
    padding: 17px 20px 20px;
}
</style>
