<template>
    <header class="operator-bar">
        <div class="operator-bar__brand xl:hidden">
            <span>B</span>
            <strong>Balafon</strong>
        </div>

        <div class="operator-bar__context">
            <p>{{ title }}</p>
            <span v-if="subtitle">{{ subtitle }}</span>
        </div>

        <div class="operator-bar__right">
            <div class="operator-clock">
                <span>{{ currentTime }}</span>
                <small>{{ currentDate }}</small>
            </div>

            <div class="operator-profile">
                <span class="operator-profile__avatar">{{ initials }}</span>
                <span class="operator-profile__copy">
                    <strong>{{ auth.user?.name ?? 'Opérateur' }}</strong>
                    <small>Régie principale</small>
                </span>
                <button class="operator-profile__logout" title="Se déconnecter" aria-label="Se déconnecter" @click="logout">
                    <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div v-if="$slots.actions" class="operator-actions">
            <slot name="actions" />
        </div>
    </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { ArrowRightStartOnRectangleIcon } from '@heroicons/vue/24/outline';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});

const auth = useAuthStore();
const router = useRouter();
const now = ref(new Date());
let clockTimer = null;

const currentTime = computed(() => now.value.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
}));
const currentDate = computed(() => now.value.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
}));
const initials = computed(() => (auth.user?.name ?? 'OP')
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase());

const logout = async () => {
    await auth.logout();
    await router.push({ name: 'login' });
};

onMounted(() => {
    clockTimer = window.setInterval(() => {
        now.value = new Date();
    }, 30000);
});

onBeforeUnmount(() => {
    if (clockTimer) {
        window.clearInterval(clockTimer);
    }
});
</script>

<style scoped>
.operator-bar {
    position: sticky;
    z-index: 40;
    top: 0;
    display: flex;
    min-height: 68px;
    align-items: center;
    gap: 16px;
    padding: 10px 12px 10px 18px;
    border: 1px solid var(--line);
    border-radius: 18px;
    background: rgba(13, 16, 19, 0.88);
    box-shadow: 0 16px 50px rgba(0, 0, 0, 0.22);
    backdrop-filter: blur(24px);
}

.operator-bar__brand {
    align-items: center;
    gap: 8px;
}

.operator-bar__brand span {
    display: grid;
    width: 32px;
    height: 32px;
    place-items: center;
    border-radius: 9px;
    background: var(--signal);
    font-weight: 800;
}

.operator-bar__brand strong {
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.92rem;
}

.operator-bar__context {
    min-width: 0;
    flex: 1;
}

.operator-bar__context p {
    margin: 0;
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.93rem;
    font-weight: 650;
}

.operator-bar__context span {
    display: none;
    max-width: 650px;
    margin-top: 3px;
    overflow: hidden;
    color: #697176;
    font-size: 0.67rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.operator-bar__right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.operator-clock {
    display: none;
    min-width: 116px;
    padding-right: 16px;
    border-right: 1px solid var(--line);
    text-align: right;
}

.operator-clock span,
.operator-clock small {
    display: block;
}

.operator-clock span {
    font-family: 'Bahnschrift', sans-serif;
    font-size: 1.02rem;
    font-weight: 650;
    letter-spacing: 0.04em;
}

.operator-clock small {
    margin-top: 2px;
    color: #687075;
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.operator-profile {
    display: flex;
    align-items: center;
    gap: 9px;
}

.operator-profile__avatar {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid var(--line-strong);
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.04);
    color: #d7d3ca;
    font-size: 0.67rem;
    font-weight: 750;
}

.operator-profile__copy {
    display: none;
    min-width: 105px;
}

.operator-profile__copy strong,
.operator-profile__copy small {
    display: block;
}

.operator-profile__copy strong {
    font-size: 0.72rem;
    font-weight: 650;
}

.operator-profile__copy small {
    margin-top: 2px;
    color: #626a6f;
    font-size: 0.58rem;
}

.operator-profile__logout {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #6d757a;
    cursor: pointer;
}

.operator-profile__logout:hover {
    background: rgba(255, 77, 46, 0.1);
    color: var(--signal-bright);
}

.operator-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

@media (min-width: 640px) {
    .operator-bar__context span,
    .operator-clock,
    .operator-profile__copy {
        display: block;
    }
}
</style>
