<template>
    <aside class="studio-sidebar">
        <RouterLink :to="{ name: 'dashboard' }" class="studio-brand">
            <span class="studio-brand__mark">B</span>
            <span>
                <strong>Balafon</strong>
                <small>Broadcast OS</small>
            </span>
        </RouterLink>

        <div class="studio-status">
            <span class="signal-dot" />
            <span>
                <small>Système de régie</small>
                <strong>Prêt à diffuser</strong>
            </span>
            <span class="studio-status__code">BTV</span>
        </div>

        <p class="studio-nav-label">Espaces de travail</p>
        <nav class="studio-nav">
            <RouterLink
                v-for="item in primaryNavigation"
                :key="item.name"
                :to="item.to"
                class="studio-nav__item"
                active-class="studio-nav__item--active"
            >
                <span class="studio-nav__index">{{ item.index }}</span>
                <component :is="item.icon" class="studio-nav__icon" />
                <span>{{ item.name }}</span>
            </RouterLink>
        </nav>

        <div class="studio-sidebar__footer">
            <RouterLink
                v-for="item in utilityNavigation"
                :key="item.name"
                :to="item.to"
                class="studio-utility"
                active-class="studio-utility--active"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span>{{ item.name }}</span>
            </RouterLink>
            <div class="studio-version">
                <span>Balafon Studio</span>
                <span>v1.0 / 2026</span>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { primaryNavigation, utilityNavigation } from '../navigation/primary';
</script>

<style scoped>
.studio-sidebar {
    position: sticky;
    top: 14px;
    display: none;
    height: calc(100vh - 28px);
    overflow: hidden;
    flex-direction: column;
    padding: 18px 14px 14px;
    border: 1px solid var(--line);
    border-radius: 22px;
    background:
        linear-gradient(160deg, rgba(255, 77, 46, 0.055), transparent 28%),
        rgba(13, 16, 19, 0.94);
    box-shadow: var(--panel-shadow);
}

.studio-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 6px 20px;
    color: var(--paper);
    text-decoration: none;
}

.studio-brand__mark {
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    border-radius: 12px;
    background: var(--signal);
    color: #fff;
    font-family: 'Bahnschrift', sans-serif;
    font-size: 1.45rem;
    font-weight: 800;
    box-shadow: 0 14px 28px rgba(255, 77, 46, 0.22);
}

.studio-brand strong,
.studio-brand small {
    display: block;
}

.studio-brand strong {
    font-family: 'Bahnschrift', sans-serif;
    font-size: 1.05rem;
    letter-spacing: 0.01em;
}

.studio-brand small {
    margin-top: 2px;
    color: #6f777c;
    font-size: 0.62rem;
    font-weight: 650;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.studio-status {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    margin-bottom: 26px;
    padding: 14px;
    border: 1px solid var(--line);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.022);
}

.studio-status small,
.studio-status strong {
    display: block;
}

.studio-status small {
    color: #6f777c;
    font-size: 0.61rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.studio-status strong {
    margin-top: 3px;
    font-size: 0.76rem;
    font-weight: 600;
}

.studio-status__code {
    color: #596066;
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.7rem;
    letter-spacing: 0.12em;
}

.studio-nav-label {
    margin: 0 10px 10px;
    color: #555d62;
    font-size: 0.59rem;
    font-weight: 700;
    letter-spacing: 0.21em;
    text-transform: uppercase;
}

.studio-nav {
    display: grid;
    gap: 3px;
}

.studio-nav__item {
    position: relative;
    display: grid;
    grid-template-columns: 24px 22px minmax(0, 1fr);
    align-items: center;
    gap: 8px;
    min-height: 46px;
    padding: 0 12px;
    border-radius: 13px;
    color: #8a9297;
    font-size: 0.82rem;
    font-weight: 550;
    text-decoration: none;
    transition: background 150ms ease, color 150ms ease, transform 150ms ease;
}

.studio-nav__item:hover {
    background: rgba(255, 255, 255, 0.035);
    color: #d9d5cd;
    transform: translateX(2px);
}

.studio-nav__item--active {
    background: linear-gradient(90deg, rgba(255, 77, 46, 0.16), rgba(255, 77, 46, 0.035));
    color: #fff;
}

.studio-nav__item--active::before {
    position: absolute;
    top: 10px;
    bottom: 10px;
    left: -14px;
    width: 3px;
    border-radius: 0 4px 4px 0;
    background: var(--signal);
    content: '';
    box-shadow: 0 0 18px rgba(255, 77, 46, 0.5);
}

.studio-nav__index {
    color: #50575c;
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.6rem;
}

.studio-nav__item--active .studio-nav__index {
    color: var(--signal-bright);
}

.studio-nav__icon {
    width: 18px;
    height: 18px;
}

.studio-sidebar__footer {
    margin-top: auto;
}

.studio-utility {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 12px;
    color: #777f84;
    font-size: 0.76rem;
    text-decoration: none;
}

.studio-utility:hover,
.studio-utility--active {
    background: rgba(255, 255, 255, 0.035);
    color: #fff;
}

.studio-version {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    padding: 14px 8px 4px;
    border-top: 1px solid var(--line);
    color: #4f565b;
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

@media (min-width: 1280px) {
    .studio-sidebar {
        display: flex;
    }
}
</style>
