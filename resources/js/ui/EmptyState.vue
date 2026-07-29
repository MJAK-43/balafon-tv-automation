<template>
    <div class="empty-state">
        <span class="empty-state__glyph"><span /></span>
        <p>{{ title }}</p>
        <small>{{ description }}</small>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<style scoped>
.empty-state {
    padding: 44px 28px;
    border: 1px dashed var(--line-strong);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.012);
    text-align: center;
}
.empty-state__glyph {
    display: grid;
    width: 42px;
    height: 42px;
    margin: 0 auto 16px;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 50%;
}
.empty-state__glyph span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #4e565b;
}
.empty-state p {
    margin: 0;
    color: var(--paper);
    font-size: 0.92rem;
    font-weight: 630;
}
.empty-state small {
    display: block;
    max-width: 460px;
    margin: 8px auto 0;
    color: #747c81;
    font-size: 0.72rem;
    line-height: 1.65;
}
</style>
