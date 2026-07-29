<template>
    <div class="workspace-shell">
        <Sidebar />

        <div class="workspace-main">
            <Topbar :title="title" :subtitle="subtitle">
                <template v-if="$slots.actions" #actions>
                    <slot name="actions" />
                </template>
            </Topbar>

            <main class="workspace-content">
                <slot />
            </main>
        </div>

        <nav class="mobile-dock xl:hidden" aria-label="Navigation principale">
            <RouterLink
                v-for="item in primaryNavigation"
                :key="item.name"
                :to="item.to"
                class="mobile-dock__item"
                active-class="mobile-dock__item--active"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span>{{ item.shortName }}</span>
            </RouterLink>
        </nav>
    </div>
</template>

<script setup>
import Sidebar from '../ui/Sidebar.vue';
import Topbar from '../ui/Topbar.vue';
import { primaryNavigation } from '../navigation/primary';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<style scoped>
.mobile-dock {
    position: fixed;
    z-index: 50;
    right: 10px;
    bottom: 10px;
    left: 10px;
    display: flex;
    gap: 4px;
    overflow-x: auto;
    padding: 6px;
    border: 1px solid var(--line-strong);
    border-radius: 18px;
    background: rgba(13, 16, 19, 0.92);
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(24px);
}

.mobile-dock__item {
    display: flex;
    min-width: 72px;
    flex: 1 0 auto;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 10px;
    border-radius: 13px;
    color: #737b80;
    font-size: 0.63rem;
    font-weight: 650;
    text-decoration: none;
}

.mobile-dock__item--active {
    background: rgba(255, 77, 46, 0.12);
    color: #fff;
}
</style>
