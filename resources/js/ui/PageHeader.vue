<template>
    <section class="page-hero">
        <div class="page-hero__signal" />
        <div class="page-hero__copy">
            <p v-if="eyebrow">{{ eyebrow }}</p>
            <h1 class="display-type">{{ title }}</h1>
            <span v-if="description">{{ description }}</span>
        </div>
        <div class="page-hero__actions">
            <slot />
        </div>
        <div class="page-hero__meta" aria-hidden="true">
            <span>Balafon / Studio</span>
            <strong>{{ year }}</strong>
        </div>
    </section>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});

const year = new Date().getFullYear();
</script>

<style scoped>
.page-hero {
    position: relative;
    display: grid;
    min-height: 210px;
    overflow: hidden;
    align-items: end;
    gap: 24px;
    padding: 28px;
    border: 1px solid var(--line);
    border-radius: 24px;
    background:
        linear-gradient(110deg, rgba(255, 77, 46, 0.095), transparent 38%),
        linear-gradient(145deg, rgba(255, 255, 255, 0.028), transparent 52%),
        #111519;
    box-shadow: var(--panel-shadow);
}

.page-hero::after {
    position: absolute;
    top: -90px;
    right: -55px;
    width: 260px;
    height: 260px;
    border: 1px solid rgba(255, 255, 255, 0.045);
    border-radius: 50%;
    content: '';
    box-shadow:
        0 0 0 34px rgba(255, 255, 255, 0.018),
        0 0 0 72px rgba(255, 255, 255, 0.012);
}

.page-hero__signal {
    position: absolute;
    top: 28px;
    bottom: 28px;
    left: 0;
    width: 4px;
    border-radius: 0 4px 4px 0;
    background: var(--signal);
    box-shadow: 0 0 28px rgba(255, 77, 46, 0.36);
}

.page-hero__copy {
    position: relative;
    z-index: 1;
    max-width: 880px;
}

.page-hero__copy p {
    margin: 0;
    color: var(--signal-bright);
    font-size: 0.64rem;
    font-weight: 720;
    letter-spacing: 0.24em;
    text-transform: uppercase;
}

.page-hero__copy h1 {
    max-width: 880px;
    margin: 12px 0 0;
    color: var(--paper);
    font-size: clamp(2.2rem, 5vw, 4.7rem);
    font-weight: 620;
    letter-spacing: -0.055em;
    line-height: 0.94;
}

.page-hero__copy span {
    display: block;
    max-width: 700px;
    margin-top: 18px;
    color: #8c9499;
    font-size: 0.82rem;
    line-height: 1.7;
}

.page-hero__actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.page-hero__meta {
    position: absolute;
    z-index: 1;
    top: 22px;
    right: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #555d62;
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.58rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.page-hero__meta strong {
    color: #777f84;
    font-weight: 500;
}

@media (min-width: 960px) {
    .page-hero {
        grid-template-columns: minmax(0, 1fr) auto;
        padding: 34px 38px;
    }

    .page-hero__actions {
        justify-content: flex-end;
    }
}
</style>
