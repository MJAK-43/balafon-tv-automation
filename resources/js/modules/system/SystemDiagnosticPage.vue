<template>
    <AppLayout title="Diagnostic système" subtitle="Contrôle technique de la plateforme et des points de connexion vMix.">
        <PageHeader
            eyebrow="Maintenance"
            title="Santé de la plateforme."
            description="Inspectez les prérequis, les services applicatifs et les connexions vMix depuis une vue technique structurée."
        >
            <RouterLink class="diagnostic-back" :to="{ name: 'dashboard' }">Retour au studio</RouterLink>
        </PageHeader>

        <section class="diagnostic-grid">
            <article class="diagnostic-panel">
                <header><span>01</span><div><p>Système</p><h2 class="display-type">Prérequis</h2></div></header>
                <pre>{{ requirements }}</pre>
            </article>
            <article class="diagnostic-panel">
                <header><span>02</span><div><p>Services</p><h2 class="display-type">État de santé</h2></div></header>
                <pre>{{ health }}</pre>
            </article>
            <article class="diagnostic-panel">
                <header><span>03</span><div><p>Connectivité</p><h2 class="display-type">Points vMix</h2></div></header>
                <pre>{{ vmix }}</pre>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHeader from '../../ui/PageHeader.vue';
import { api } from '../../services/api';

const requirements = ref({});
const health = ref({});
const vmix = ref([]);

onMounted(async () => {
    const [requirementsResponse, healthResponse, vmixResponse] = await Promise.all([
        api.get('/system/requirements'),
        api.get('/system/health'),
        api.get('/system/vmix'),
    ]);
    requirements.value = requirementsResponse.data;
    health.value = healthResponse.data;
    vmix.value = vmixResponse.data;
});
</script>

<style scoped>
.diagnostic-back {
    padding: 11px 16px;
    border: 1px solid var(--line-strong);
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.025);
    color: #d7d3ca;
    font-size: 0.74rem;
    text-decoration: none;
}
.diagnostic-grid {
    display: grid;
    gap: 14px;
}
.diagnostic-panel {
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 20px;
    background: rgba(17, 21, 25, 0.9);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.2);
}
.diagnostic-panel header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--line);
}
.diagnostic-panel header > span {
    color: var(--signal-bright);
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.62rem;
}
.diagnostic-panel p {
    margin: 0;
    color: #656d72;
    font-size: 0.55rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.diagnostic-panel h2 {
    margin: 4px 0 0;
    font-size: 1.08rem;
    font-weight: 620;
}
.diagnostic-panel pre {
    max-height: 480px;
    margin: 0;
    overflow: auto;
    padding: 20px;
    color: #aab1b4;
    font-family: 'Cascadia Code', 'Consolas', monospace;
    font-size: 0.7rem;
    line-height: 1.65;
    white-space: pre-wrap;
}
@media (min-width: 1100px) {
    .diagnostic-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
</style>
