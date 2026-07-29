<template>
    <AppLayout title="Playlists" subtitle="Edition claire des sequences, avec construction visuelle et duree cumulee.">
        <PageHeader
            eyebrow="Séquençage"
            title="Composez votre antenne."
            description="Construisez une séquence, ajoutez les médias et maîtrisez précisément la durée totale."
        >
            <button class="rounded-full border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-200 transition hover:bg-white/[0.06]" @click="createNewPlaylist">
                Nouvelle playlist
            </button>
            <button class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)] transition hover:bg-amber-400" @click="savePlaylist">
                Enregistrer
            </button>
        </PageHeader>

        <section class="grid gap-5 xl:grid-cols-[280px_minmax(0,1.2fr)_320px]">
            <div class="space-y-4">
                <TimelineCard eyebrow="Bibliotheque" title="Mes playlists">
                    <SearchBar v-model="playlistSearch" placeholder="Rechercher une playlist" />
                    <div class="mt-4 space-y-3">
                        <PlaylistCard
                            v-for="playlist in filteredPlaylists"
                            :key="playlist.uuid"
                            :active="playlist.uuid === selectedPlaylistUuid"
                            :title="playlist.title"
                            :subtitle="playlist.status"
                            :description="playlist.description"
                            :items-label="`${playlist.items_count ?? playlist.items?.length ?? 0} elements`"
                            :duration-label="formatDuration(playlist.total_duration_seconds ?? 0)"
                            @click="selectPlaylist(playlist)"
                        >
                            <template #badge>
                                <StatusBadge :status="playlist.status" />
                            </template>
                        </PlaylistCard>
                        <EmptyState
                            v-if="filteredPlaylists.length === 0"
                            title="Aucune playlist"
                            description="Creez une nouvelle playlist ou affinez votre recherche."
                        />
                    </div>
                </TimelineCard>
            </div>

            <div class="space-y-4">
                <TimelineCard eyebrow="Edition" title="Parametres">
                    <template #actions>
                        <button
                            v-if="selectedPlaylistUuid"
                            class="rounded-full border border-rose-400/20 bg-rose-500/10 px-4 py-2 text-xs text-rose-200"
                            @click="deleteCurrentPlaylist"
                        >
                            Supprimer
                        </button>
                    </template>

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="space-y-2 text-sm md:col-span-2">
                                <span class="text-slate-400">Titre</span>
                                <input v-model="editor.title" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none" type="text">
                            </label>
                            <label class="space-y-2 text-sm md:col-span-2">
                                <span class="text-slate-400">Description</span>
                                <textarea v-model="editor.description" class="min-h-24 w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none" />
                            </label>
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-400">Statut</span>
                                <select v-model="editor.status" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none">
                                    <option value="DRAFT">DRAFT</option>
                                    <option value="READY">READY</option>
                                    <option value="ARCHIVED">ARCHIVED</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-[1.25rem] border border-white/10 bg-slate-950/45 p-4">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Elements</p>
                                <p class="mt-3 text-3xl font-semibold text-white">{{ editor.items.length }}</p>
                            </div>
                            <div class="rounded-[1.25rem] border border-white/10 bg-slate-950/45 p-4">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Duree totale</p>
                                <p class="mt-3 text-2xl font-semibold text-white">{{ formatDuration(totalDurationSeconds) }}</p>
                            </div>
                        </div>
                    </div>
                </TimelineCard>

                <TimelineCard eyebrow="Sequence" title="Ordre de diffusion">
                    <div v-if="editor.items.length === 0">
                        <EmptyState title="Playlist vide" description="Ajoutez un media depuis la colonne de droite." />
                    </div>
                    <div v-else class="sequence-list">
                        <article
                            v-for="(item, index) in editor.items"
                            :key="item.uuid || `${item.media_asset_id}-${index}`"
                            draggable="true"
                            class="sequence-item"
                            @dragstart="dragIndex = index"
                            @dragover.prevent
                            @drop="dropItem(index)"
                        >
                            <div class="sequence-item__top">
                                <div class="sequence-item__index">
                                    <span>{{ String(index + 1).padStart(2, '0') }}</span>
                                    <i aria-hidden="true" />
                                </div>
                                <div class="sequence-item__identity">
                                    <p>{{ item.media_asset?.title || 'Média sans titre' }}</p>
                                    <span>{{ item.media_asset?.media_type || 'MÉDIA' }}</span>
                                </div>
                                <div class="sequence-item__actions">
                                    <button class="sequence-action sequence-action--graphics" type="button" @click="openGraphicsDialog(index)">
                                        Habillage
                                    </button>
                                    <button class="sequence-action sequence-action--remove" type="button" @click="removeItem(index)">
                                        Retirer
                                    </button>
                                </div>
                            </div>

                            <div class="sequence-item__details">
                                <div class="sequence-metric">
                                    <span>Durée</span>
                                    <strong>{{ formatDuration(item.media_asset?.duration_seconds ?? 0) }}</strong>
                                </div>
                                <div class="sequence-metric">
                                    <span>Cumulé</span>
                                    <strong>{{ formatDuration(cumulativeDuration(index)) }}</strong>
                                </div>
                                <div class="sequence-assets" :class="{ 'sequence-assets--empty': !item.logo && !item.announcement }">
                                    <span v-if="!item.logo && !item.announcement" class="sequence-assets__empty">
                                        Aucun habillage
                                    </span>
                                    <span v-if="item.logo" class="sequence-chip sequence-chip--logo" :title="item.logo.name">
                                        <b>Logo</b>
                                        {{ item.logo.name }}
                                    </span>
                                    <span v-if="item.announcement" class="sequence-chip sequence-chip--announcement" :title="item.announcement.name">
                                        <b>Bande</b>
                                        {{ item.announcement.name }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>
                </TimelineCard>
            </div>

            <div class="space-y-4">
                <FilterPanel title="Media disponible" @reset="librarySearch = ''">
                    <SearchBar v-model="librarySearch" placeholder="Rechercher un media" />
                    <div class="text-xs uppercase tracking-[0.22em] text-slate-500">{{ libraryResults.length }} medias disponibles</div>
                </FilterPanel>

                <div class="space-y-3">
                    <SchedulerCard
                        v-for="media in libraryResults"
                        :key="media.uuid"
                        :title="media.title"
                        :meta="`${media.media_type} - ${formatDuration(media.duration_seconds ?? 0)}`"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <StatusBadge :status="media.status" />
                            <button class="rounded-full bg-white/5 px-4 py-2 text-xs text-slate-200 transition hover:bg-white/10" @click="addMedia(media)">Ajouter</button>
                        </div>
                    </SchedulerCard>
                    <EmptyState
                        v-if="libraryResults.length === 0"
                        title="Aucun media"
                        description="Aucun media READY ne correspond a la recherche."
                    />
                </div>
            </div>
        </section>

        <Dialog v-model:visible="graphicsDialogVisible" modal header="Logo et bande d'annonce" :style="{ width: '42rem' }">
            <div class="space-y-5">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-900">
                        <input v-model="graphicsForm.logo_enabled" type="checkbox">
                        <span>Afficher un logo sur ce media</span>
                    </label>
                    <label v-if="graphicsForm.logo_enabled" class="mt-4 block space-y-2 text-sm text-slate-600">
                        <span>Choisir un logo existant</span>
                        <select v-model="graphicsForm.logo_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900">
                            <option :value="null" disabled>Selectionner un logo</option>
                            <option v-for="logo in activeLogos" :key="logo.uuid" :value="logo.id">{{ logo.name }}</option>
                        </select>
                    </label>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-900">
                        <input v-model="graphicsForm.announcement_enabled" type="checkbox">
                        <span>Afficher une bande d'annonce sur ce media</span>
                    </label>
                    <label v-if="graphicsForm.announcement_enabled" class="mt-4 block space-y-2 text-sm text-slate-600">
                        <span>Choisir une bande existante</span>
                        <select v-model="graphicsForm.announcement_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900">
                            <option :value="null" disabled>Selectionner une bande</option>
                            <option v-for="announcement in activeAnnouncements" :key="announcement.uuid" :value="announcement.id">
                                {{ announcement.name }} · {{ announcement.asset_type === 'ANNOUNCEMENT_TEXT' ? 'Texte' : 'Video' }}
                            </option>
                        </select>
                    </label>
                </div>

                <p v-if="activeLogos.length === 0 || activeAnnouncements.length === 0" class="text-sm text-slate-500">
                    Les listes proviennent de la rubrique Logos & bandes. Creez vos habillages dans cette rubrique avant de les selectionner ici.
                </p>

                <div class="flex justify-end gap-3">
                    <button class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600" @click="graphicsDialogVisible = false">Annuler</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-medium text-white disabled:opacity-40" :disabled="!canApplyGraphics" @click="applyGraphics">
                        Appliquer
                    </button>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import Dialog from 'primevue/dialog';
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useBroadcastStore } from '../../stores/broadcast';
import EmptyState from '../../ui/EmptyState.vue';
import FilterPanel from '../../ui/FilterPanel.vue';
import PageHeader from '../../ui/PageHeader.vue';
import PlaylistCard from '../../ui/PlaylistCard.vue';
import SchedulerCard from '../../ui/SchedulerCard.vue';
import SearchBar from '../../ui/SearchBar.vue';
import StatusBadge from '../../ui/StatusBadge.vue';
import TimelineCard from '../../ui/TimelineCard.vue';

const broadcast = useBroadcastStore();
const playlistSearch = ref('');
const librarySearch = ref('');
const selectedPlaylistUuid = ref(null);
const dragIndex = ref(null);
const graphicsDialogVisible = ref(false);
const graphicsItemIndex = ref(null);

const editor = reactive({
    title: '',
    description: '',
    status: 'DRAFT',
    items: [],
});
const graphicsForm = reactive({
    logo_enabled: false,
    logo_id: null,
    announcement_enabled: false,
    announcement_id: null,
});

const playlists = computed(() => broadcast.playlists);
const brandingAssets = computed(() => broadcast.brandingAssets);
const activeLogos = computed(() => brandingAssets.value.filter((asset) => asset.asset_type === 'LOGO' && asset.status === 'ACTIVE'));
const activeAnnouncements = computed(() => brandingAssets.value.filter((asset) => asset.asset_type !== 'LOGO' && asset.status === 'ACTIVE'));
const mediaAssets = computed(() => broadcast.mediaAssets.filter((item) => item.status === 'READY'));
const filteredPlaylists = computed(() => playlists.value.filter((playlist) => {
    const query = playlistSearch.value.trim().toLowerCase();

    return query === ''
        || playlist.title.toLowerCase().includes(query)
        || (playlist.description ?? '').toLowerCase().includes(query);
}));
const libraryResults = computed(() => mediaAssets.value.filter((item) => {
    const query = librarySearch.value.trim().toLowerCase();

    return query === ''
        || item.title.toLowerCase().includes(query)
        || item.media_type.toLowerCase().includes(query);
}));
const totalDurationSeconds = computed(() => editor.items.reduce((carry, item) => carry + (item.media_asset?.duration_seconds ?? 0), 0));
const canApplyGraphics = computed(() => (
    (!graphicsForm.logo_enabled || graphicsForm.logo_id !== null)
    && (!graphicsForm.announcement_enabled || graphicsForm.announcement_id !== null)
));

const load = async () => {
    await Promise.all([
        broadcast.fetchMediaAssets({ mode: 'all' }),
        broadcast.fetchPlaylists(),
        broadcast.fetchBrandingAssets(),
    ]);

    if (playlists.value.length > 0) {
        selectPlaylist(playlists.value[0]);
    } else {
        createNewPlaylist();
    }
};

const cloneItems = (items = []) => items.map((item, index) => ({
    uuid: item.uuid ?? null,
    media_asset_id: item.media_asset_id ?? item.media_asset?.id,
    position: index + 1,
    media_asset: item.media_asset ?? mediaAssets.value.find((asset) => asset.id === item.media_asset_id) ?? null,
    logo_id: item.logo_id ?? item.logo?.id ?? null,
    announcement_id: item.announcement_id ?? item.announcement?.id ?? null,
    logo: item.logo ?? brandingAssets.value.find((asset) => asset.id === item.logo_id) ?? null,
    announcement: item.announcement ?? brandingAssets.value.find((asset) => asset.id === item.announcement_id) ?? null,
}));

const selectPlaylist = (playlist) => {
    selectedPlaylistUuid.value = playlist.uuid;
    editor.title = playlist.title;
    editor.description = playlist.description ?? '';
    editor.status = playlist.status;
    editor.items = cloneItems(playlist.items ?? []);
};

const createNewPlaylist = () => {
    selectedPlaylistUuid.value = null;
    editor.title = '';
    editor.description = '';
    editor.status = 'DRAFT';
    editor.items = [];
};

const addMedia = (media) => {
    editor.items.push({
        uuid: null,
        media_asset_id: media.id,
        position: editor.items.length + 1,
        media_asset: media,
        logo_id: null,
        announcement_id: null,
        logo: null,
        announcement: null,
    });
    normalizePositions();
};

const removeItem = (index) => {
    editor.items.splice(index, 1);
    normalizePositions();
};

const normalizePositions = () => {
    editor.items = editor.items.map((item, index) => ({
        ...item,
        position: index + 1,
    }));
};

const dropItem = (index) => {
    if (dragIndex.value === null || dragIndex.value === index) {
        dragIndex.value = null;
        return;
    }

    const [moved] = editor.items.splice(dragIndex.value, 1);
    editor.items.splice(index, 0, moved);
    dragIndex.value = null;
    normalizePositions();
};

const cumulativeDuration = (index) => editor.items
    .slice(0, index + 1)
    .reduce((carry, item) => carry + (item.media_asset?.duration_seconds ?? 0), 0);

const openGraphicsDialog = (index) => {
    const item = editor.items[index];
    graphicsItemIndex.value = index;
    Object.assign(graphicsForm, {
        logo_enabled: item.logo_id !== null,
        logo_id: item.logo_id,
        announcement_enabled: item.announcement_id !== null,
        announcement_id: item.announcement_id,
    });
    graphicsDialogVisible.value = true;
};

const applyGraphics = () => {
    if (graphicsItemIndex.value === null || !canApplyGraphics.value) {
        return;
    }

    const item = editor.items[graphicsItemIndex.value];
    item.logo_id = graphicsForm.logo_enabled ? graphicsForm.logo_id : null;
    item.announcement_id = graphicsForm.announcement_enabled ? graphicsForm.announcement_id : null;
    item.logo = brandingAssets.value.find((asset) => asset.id === item.logo_id) ?? null;
    item.announcement = brandingAssets.value.find((asset) => asset.id === item.announcement_id) ?? null;
    graphicsDialogVisible.value = false;
};

const savePlaylist = async () => {
    const payload = {
        title: editor.title,
        description: editor.description || null,
        status: editor.status,
        items: editor.items.map((item, index) => ({
            media_asset_id: item.media_asset_id,
            position: index + 1,
            logo_id: item.logo_id,
            announcement_id: item.announcement_id,
        })),
    };

    const record = selectedPlaylistUuid.value
        ? await broadcast.updatePlaylist(selectedPlaylistUuid.value, payload)
        : await broadcast.createPlaylist(payload);

    await broadcast.fetchPlaylists();
    const fresh = playlists.value.find((playlist) => playlist.uuid === record.uuid) ?? record;
    selectPlaylist(fresh);
};

const deleteCurrentPlaylist = async () => {
    if (!selectedPlaylistUuid.value) {
        return;
    }

    await broadcast.deletePlaylist(selectedPlaylistUuid.value);
    if (playlists.value.length > 0) {
        selectPlaylist(playlists.value[0]);
    } else {
        createNewPlaylist();
    }
};

const formatDuration = (seconds) => {
    const safe = Number(seconds ?? 0);
    const hrs = Math.floor(safe / 3600);
    const mins = Math.floor((safe % 3600) / 60);
    const secs = safe % 60;

    return [hrs, mins, secs].map((value) => String(value).padStart(2, '0')).join(':');
};

onMounted(load);
</script>

<style scoped>
.sequence-list {
    display: grid;
    gap: 12px;
}

.sequence-item {
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 17px;
    background:
        linear-gradient(105deg, rgba(255, 255, 255, 0.035), transparent 48%),
        rgba(8, 10, 12, 0.5);
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.14);
    cursor: grab;
    transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
}

.sequence-item:hover {
    border-color: var(--line-strong);
    background:
        linear-gradient(105deg, rgba(255, 77, 46, 0.045), transparent 48%),
        rgba(8, 10, 12, 0.62);
    transform: translateY(-1px);
}

.sequence-item:active {
    cursor: grabbing;
}

.sequence-item__top {
    display: grid;
    grid-template-columns: 50px minmax(0, 1fr) auto;
    align-items: center;
    gap: 14px;
    padding: 16px;
}

.sequence-item__index {
    position: relative;
    display: flex;
    width: 48px;
    height: 48px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border: 1px solid rgba(255, 106, 77, 0.2);
    border-radius: 14px;
    color: var(--signal-bright);
    background: rgba(255, 77, 46, 0.09);
    font-size: 0.78rem;
    font-variant-numeric: tabular-nums;
    font-weight: 800;
}

.sequence-item__index i,
.sequence-item__index i::before,
.sequence-item__index i::after {
    display: block;
    width: 14px;
    height: 1px;
    background: rgba(255, 106, 77, 0.55);
    content: '';
}

.sequence-item__index i {
    position: relative;
}

.sequence-item__index i::before {
    position: absolute;
    top: -3px;
}

.sequence-item__index i::after {
    position: absolute;
    top: 3px;
}

.sequence-item__identity {
    min-width: 0;
}

.sequence-item__identity p {
    overflow: hidden;
    margin: 0;
    color: var(--paper);
    font-size: 0.95rem;
    font-weight: 650;
    letter-spacing: -0.012em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sequence-item__identity > span {
    display: block;
    margin-top: 5px;
    color: var(--muted);
    font-size: 0.58rem;
    font-weight: 720;
    letter-spacing: 0.17em;
    text-transform: uppercase;
}

.sequence-item__actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.sequence-action {
    min-height: 34px;
    padding: 0 12px;
    border: 1px solid var(--line);
    border-radius: 10px;
    font-size: 0.67rem;
    font-weight: 680;
    transition: border-color 160ms ease, background 160ms ease, color 160ms ease;
}

.sequence-action--graphics {
    border-color: rgba(255, 106, 77, 0.28);
    color: #ffc1b4;
    background: rgba(255, 77, 46, 0.08);
}

.sequence-action--graphics:hover {
    border-color: rgba(255, 106, 77, 0.5);
    background: rgba(255, 77, 46, 0.14);
}

.sequence-action--remove {
    color: #bdc2c4;
    background: rgba(255, 255, 255, 0.025);
}

.sequence-action--remove:hover {
    border-color: rgba(255, 92, 117, 0.38);
    color: #ffb1bd;
    background: rgba(255, 92, 117, 0.08);
}

.sequence-item__details {
    display: grid;
    grid-template-columns: 100px 110px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    margin-left: 80px;
    padding: 13px 16px 15px 0;
    border-top: 1px solid var(--line);
}

.sequence-metric {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.sequence-metric span {
    color: #666e73;
    font-size: 0.54rem;
    font-weight: 750;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.sequence-metric strong {
    color: var(--paper);
    font-size: 0.82rem;
    font-variant-numeric: tabular-nums;
    font-weight: 650;
}

.sequence-assets {
    display: flex;
    min-width: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
}

.sequence-assets--empty {
    align-items: center;
}

.sequence-assets__empty {
    color: #5d656a;
    font-size: 0.63rem;
    font-style: italic;
}

.sequence-chip {
    display: inline-flex;
    min-width: 0;
    max-width: 210px;
    align-items: center;
    gap: 6px;
    overflow: hidden;
    padding: 6px 9px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font-size: 0.59rem;
    font-weight: 620;
    letter-spacing: 0.055em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sequence-chip b {
    flex: 0 0 auto;
    font-size: 0.5rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.sequence-chip--logo {
    border-color: rgba(255, 200, 87, 0.25);
    color: #f5d98f;
    background: rgba(255, 200, 87, 0.07);
}

.sequence-chip--announcement {
    border-color: rgba(65, 205, 224, 0.24);
    color: #9de7f0;
    background: rgba(65, 205, 224, 0.07);
}

@media (max-width: 720px) {
    .sequence-item__top {
        grid-template-columns: 46px minmax(0, 1fr);
        gap: 11px;
    }

    .sequence-item__index {
        width: 44px;
        height: 44px;
    }

    .sequence-item__actions {
        grid-column: 2;
        justify-content: flex-start;
    }

    .sequence-item__details {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-left: 0;
        padding: 13px 16px 15px;
    }

    .sequence-assets {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 420px) {
    .sequence-item__top {
        padding: 13px;
    }

    .sequence-item__actions {
        align-items: stretch;
        flex-direction: column;
    }

    .sequence-action {
        width: 100%;
    }

    .sequence-chip {
        max-width: 100%;
    }
}
</style>
