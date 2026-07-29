<template>
    <AppLayout title="Vue générale" subtitle="État de la plateforme, connexion vMix et activité récente.">
        <PageHeader
            eyebrow="Centre opérationnel"
            title="La régie, en un regard."
            description="Surveillez la diffusion, la santé de vMix et l’activité du studio depuis un cockpit unique."
        >
            <button class="action-secondary" @click="playTest">Tester la lecture</button>
            <button class="bg-amber-500 rounded-xl px-5 py-3 text-sm font-semibold" @click="runDiagnostic">Lancer le diagnostic</button>
        </PageHeader>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            <StatCard v-for="card in summaryCards" :key="card.label" :label="card.label" :value="card.value" :hint="card.hint" />
        </section>

        <section class="dashboard-grid">
            <article class="dashboard-panel dashboard-panel--live">
                <div class="dashboard-panel__header">
                    <div>
                        <p>Signal vMix</p>
                        <h2 class="display-type">État antenne</h2>
                    </div>
                    <span :class="summary?.vmix?.connected ? 'live-state--online' : 'live-state--offline'" class="live-state">
                        <i /> {{ summary?.vmix?.connected ? 'Connecté' : 'Hors ligne' }}
                    </span>
                </div>

                <div class="live-stage">
                    <div>
                        <p>Entrée programme</p>
                        <strong class="display-type">{{ summary?.vmix?.active_input ?? 'Aucun signal' }}</strong>
                        <span>vMix {{ summary?.vmix?.version ?? 'n/a' }} / {{ summary?.vmix?.edition ?? 'édition inconnue' }}</span>
                    </div>
                    <div class="live-stage__rings"><i /><i /><i /></div>
                </div>

                <div class="live-flags">
                    <div><span>Preview</span><strong>{{ summary?.vmix?.preview_input ?? 'n/a' }}</strong></div>
                    <div><span>Streaming</span><strong>{{ summary?.vmix?.streaming ? 'ON' : 'OFF' }}</strong></div>
                    <div><span>Enregistrement</span><strong>{{ summary?.vmix?.recording ? 'ON' : 'OFF' }}</strong></div>
                    <div><span>Sortie externe</span><strong>{{ summary?.vmix?.external ? 'ON' : 'OFF' }}</strong></div>
                </div>
            </article>

            <article class="dashboard-panel">
                <div class="dashboard-panel__header">
                    <div>
                        <p>Configuration</p>
                        <h2 class="display-type">Pont vMix</h2>
                    </div>
                    <button class="action-quiet" @click="testConnection">Tester</button>
                </div>

                <div class="connection-form">
                    <label><span>Nom</span><input v-model="connectionForm.name"></label>
                    <label><span>Hôte</span><input v-model="connectionForm.host"></label>
                    <label><span>Port</span><input v-model.number="connectionForm.port" type="number"></label>
                    <label><span>Timeout</span><input v-model.number="connectionForm.timeout_ms" type="number"></label>
                </div>
                <button class="connection-save" @click="saveConnection">Enregistrer la connexion</button>
                <p v-if="connectionMessage" class="mt-3 text-xs text-emerald-300">{{ connectionMessage }}</p>
                <p v-if="summary?.vmix?.error" class="mt-3 text-xs text-rose-300">{{ summary.vmix.error }}</p>
            </article>
        </section>

        <section class="dashboard-grid dashboard-grid--activity">
            <article class="dashboard-panel">
                <div class="dashboard-panel__header">
                    <div><p>Journal</p><h2 class="display-type">Activité récente</h2></div>
                    <span class="panel-count">{{ summary?.latest_audits?.length ?? 0 }} événements</span>
                </div>
                <div class="event-list">
                    <div v-for="item in summary?.latest_audits ?? []" :key="item.uuid" class="event-row">
                        <i />
                        <div><strong>{{ item.action }}</strong><span>{{ item.entity_type }} · {{ item.created_at }}</span></div>
                    </div>
                    <EmptyState v-if="(summary?.latest_audits?.length ?? 0) === 0" title="Aucune activité" description="Les événements opérateur apparaîtront ici." />
                </div>
            </article>

            <article class="dashboard-panel">
                <div class="dashboard-panel__header">
                    <div><p>Commandes</p><h2 class="display-type">Derniers tests vMix</h2></div>
                </div>
                <div class="event-list">
                    <div v-for="item in summary?.latest_vmix_tests ?? []" :key="item.uuid" class="event-row">
                        <i :class="item.status === 'success' ? 'event-row__ok' : 'event-row__error'" />
                        <div><strong>{{ item.command_name }}</strong><span>{{ item.status }} · {{ item.executed_at }}</span></div>
                    </div>
                    <EmptyState v-if="(summary?.latest_vmix_tests?.length ?? 0) === 0" title="Aucun test" description="Les validations vMix apparaîtront ici." />
                </div>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import EmptyState from '../../ui/EmptyState.vue';
import PageHeader from '../../ui/PageHeader.vue';
import StatCard from '../../ui/StatCard.vue';
import { api } from '../../services/api';

const summary = ref(null);
const connectionMessage = ref('');
const connectionForm = reactive({
    uuid: null,
    name: 'vMix local',
    host: 'host.docker.internal',
    port: 8088,
    timeout_ms: 3000,
    is_active: true,
});
let timer = null;

const systemLabel = computed(() => {
    const system = summary.value?.system;
    return system ? `${system.os_name} ${system.os_version ?? ''}`.trim() : 'n/a';
});

const summaryCards = computed(() => [
    { label: 'Système', value: systemLabel.value, hint: 'machine hôte' },
    { label: 'vMix', value: summary.value?.vmix?.connected ? 'Connecté' : 'Offline', hint: 'pont temps réel' },
    { label: 'Version', value: summary.value?.vmix?.version ?? 'n/a', hint: 'version moteur' },
    { label: 'Entrées', value: summary.value?.vmix?.inputs_count ?? 0, hint: 'sources disponibles' },
    { label: 'Canaux', value: summary.value?.metrics?.channels_count ?? 0, hint: 'voies de diffusion' },
    { label: 'Playlists', value: summary.value?.metrics?.playlists_count ?? 0, hint: 'séquences prêtes' },
]);

const fetchSummary = async () => {
    const { data } = await api.get('/dashboard/summary');
    summary.value = data;
    const firstConnection = data?.connections?.[0];
    if (firstConnection) {
        Object.assign(connectionForm, firstConnection);
    }
};

const runDiagnostic = async () => {
    await api.post('/system/run-diagnostic');
    await fetchSummary();
};
const playTest = async () => {
    await api.post('/vmix/play-test');
    await fetchSummary();
};
const saveConnection = async () => {
    connectionMessage.value = '';
    await api.put(`/vmix/connections/${connectionForm.uuid}`, {
        name: connectionForm.name,
        host: connectionForm.host,
        port: connectionForm.port,
        timeout_ms: connectionForm.timeout_ms,
        is_active: connectionForm.is_active,
    });
    connectionMessage.value = 'Connexion mise à jour.';
    await fetchSummary();
};
const testConnection = async () => {
    connectionMessage.value = '';
    await api.post(`/vmix/connections/${connectionForm.uuid}/test`);
    connectionMessage.value = 'Test de connexion terminé.';
    await fetchSummary();
};

onMounted(async () => {
    await fetchSummary();
    timer = window.setInterval(fetchSummary, 30000);
});
onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
});
</script>

<style scoped>
.action-secondary,
.action-quiet {
    border: 1px solid var(--line-strong);
    background: rgba(255, 255, 255, 0.03);
    color: #d7d3ca;
}
.action-secondary {
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 0.78rem;
}
.action-quiet {
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 0.67rem;
}
.dashboard-grid {
    display: grid;
    gap: 14px;
}
.dashboard-panel {
    overflow: hidden;
    padding: 20px;
    border: 1px solid var(--line);
    border-radius: 21px;
    background: rgba(17, 21, 25, 0.9);
    box-shadow: 0 22px 58px rgba(0, 0, 0, 0.22);
}
.dashboard-panel--live {
    background:
        radial-gradient(circle at 85% 12%, rgba(184, 244, 92, 0.07), transparent 16rem),
        rgba(17, 21, 25, 0.92);
}
.dashboard-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 17px;
    border-bottom: 1px solid var(--line);
}
.dashboard-panel__header p {
    margin: 0;
    color: var(--signal-bright);
    font-size: 0.56rem;
    font-weight: 720;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.dashboard-panel__header h2 {
    margin: 6px 0 0;
    font-size: 1.15rem;
    font-weight: 620;
}
.live-state {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    border: 1px solid var(--line);
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 650;
}
.live-state i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}
.live-state--online { color: var(--live); }
.live-state--online i { background: var(--live); box-shadow: 0 0 14px rgba(184, 244, 92, 0.5); }
.live-state--offline { color: var(--danger); }
.live-state--offline i { background: var(--danger); }
.live-stage {
    position: relative;
    min-height: 195px;
    overflow: hidden;
    padding: 30px 0;
}
.live-stage p {
    margin: 0;
    color: #666e73;
    font-size: 0.61rem;
    font-weight: 650;
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.live-stage strong {
    display: block;
    max-width: 80%;
    margin-top: 16px;
    overflow: hidden;
    font-size: clamp(2rem, 4vw, 3.7rem);
    font-weight: 600;
    letter-spacing: -0.055em;
    line-height: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.live-stage span {
    display: block;
    margin-top: 15px;
    color: #747c81;
    font-size: 0.7rem;
}
.live-stage__rings {
    position: absolute;
    right: 0;
    bottom: 4px;
    width: 145px;
    height: 145px;
}
.live-stage__rings i {
    position: absolute;
    inset: 0;
    border: 1px solid rgba(184, 244, 92, 0.12);
    border-radius: 50%;
}
.live-stage__rings i:nth-child(2) { inset: 28px; }
.live-stage__rings i:nth-child(3) { inset: 56px; background: var(--live); box-shadow: 0 0 32px rgba(184, 244, 92, 0.22); }
.live-flags {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    border-top: 1px solid var(--line);
}
.live-flags div {
    padding: 15px 8px 2px;
}
.live-flags span,
.live-flags strong {
    display: block;
}
.live-flags span {
    color: #5f676c;
    font-size: 0.56rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.live-flags strong {
    margin-top: 6px;
    font-size: 0.75rem;
}
.connection-form {
    display: grid;
    gap: 13px;
    margin-top: 20px;
}
.connection-form label span {
    display: block;
    margin-bottom: 7px;
    color: #737b80;
    font-size: 0.62rem;
}
.connection-form input {
    width: 100%;
    min-height: 45px;
    padding: 0 13px;
    border-radius: 11px;
}
.connection-save {
    width: 100%;
    min-height: 45px;
    margin-top: 16px;
    border: 0;
    border-radius: 11px;
    background: var(--paper);
    color: #101316;
    font-size: 0.72rem;
    font-weight: 720;
}
.panel-count {
    color: #636b70;
    font-size: 0.58rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.event-list {
    display: grid;
    gap: 4px;
    margin-top: 12px;
}
.event-row {
    display: grid;
    grid-template-columns: 8px minmax(0, 1fr);
    align-items: center;
    gap: 13px;
    padding: 12px 4px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.055);
}
.event-row > i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #5d656a;
}
.event-row > .event-row__ok { background: var(--live); }
.event-row > .event-row__error { background: var(--danger); }
.event-row strong,
.event-row span {
    display: block;
}
.event-row strong {
    overflow: hidden;
    font-size: 0.72rem;
    font-weight: 620;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.event-row span {
    margin-top: 4px;
    color: #626a6f;
    font-size: 0.61rem;
}
@media (min-width: 640px) {
    .connection-form { grid-template-columns: repeat(2, 1fr); }
    .live-flags { grid-template-columns: repeat(4, 1fr); }
}
@media (min-width: 1180px) {
    .dashboard-grid { grid-template-columns: minmax(0, 1.35fr) minmax(360px, 0.65fr); }
    .dashboard-grid--activity { grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr); }
}
</style>
