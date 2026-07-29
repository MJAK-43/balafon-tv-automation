<template>
    <AppLayout title="Logos & bandes" subtitle="Bibliotheque d'habillages reutilisables pour vos playlists.">
        <PageHeader
            eyebrow="Identité antenne"
            title="Votre marque, toujours à l’image."
            description="Importez les logos, préparez les textes défilants ou ajoutez une bande vidéo, puis réutilisez-les dans chaque playlist."
        >
            <button
                v-if="activeTab === 'logos'"
                class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)]"
                @click="openLogoDialog"
            >
                Ajouter un logo
            </button>
            <button
                v-else
                class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)]"
                @click="openAnnouncementDialog"
            >
                Nouvelle bande
            </button>
        </PageHeader>

        <section class="grid gap-4 md:grid-cols-3">
            <StatCard label="Logos actifs" :value="activeLogos.length" hint="PNG disponibles" />
            <StatCard label="Bandes texte" :value="textAnnouncements.length" hint="messages enregistres" />
            <StatCard label="Bandes video" :value="videoAnnouncements.length" hint="animations disponibles" />
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-white/8 bg-[#0f1728]/82 shadow-[0_24px_70px_rgba(0,0,0,0.2)]">
            <div class="flex border-b border-white/8 p-2">
                <button
                    class="flex-1 rounded-[1.25rem] px-5 py-3 text-sm font-medium transition"
                    :class="activeTab === 'logos' ? 'bg-amber-500 text-slate-950' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                    @click="activeTab = 'logos'"
                >
                    Logos
                </button>
                <button
                    class="flex-1 rounded-[1.25rem] px-5 py-3 text-sm font-medium transition"
                    :class="activeTab === 'announcements' ? 'bg-amber-500 text-slate-950' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                    @click="activeTab = 'announcements'"
                >
                    Bandes d'annonce
                </button>
            </div>

            <div class="p-5">
                <div v-if="loading">
                    <LoadingState />
                </div>
                <EmptyState
                    v-else-if="visibleAssets.length === 0"
                    :title="activeTab === 'logos' ? 'Aucun logo' : 'Aucune bande d’annonce'"
                    :description="activeTab === 'logos' ? 'Importez votre premier logo PNG depuis ce PC.' : 'Enregistrez un texte defilant ou importez une video depuis ce PC.'"
                />
                <div v-else class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    <article
                        v-for="asset in visibleAssets"
                        :key="asset.uuid"
                        class="group overflow-hidden rounded-[1.75rem] border border-white/8 bg-slate-950/45"
                    >
                        <div
                            class="relative flex h-44 items-center justify-center overflow-hidden border-b border-white/8"
                            :class="asset.asset_type === 'LOGO'
                                ? 'bg-[linear-gradient(45deg,#172033_25%,transparent_25%),linear-gradient(-45deg,#172033_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#172033_75%),linear-gradient(-45deg,transparent_75%,#172033_75%)] bg-[length:24px_24px] bg-[position:0_0,0_12px,12px_-12px,-12px_0]'
                                : 'bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.18),transparent_42%),#0b1220]'"
                            :style="asset.asset_type === 'ANNOUNCEMENT_TEXT' ? { backgroundColor: asset.text_background_color } : null"
                        >
                            <img
                                v-if="asset.asset_type === 'LOGO' && assetPreviewUrls[asset.uuid]"
                                :src="assetPreviewUrls[asset.uuid]"
                                :alt="`Logo ${asset.name}`"
                                class="max-h-[78%] max-w-[78%] object-contain drop-shadow-[0_12px_24px_rgba(0,0,0,0.35)]"
                            >
                            <div v-else-if="asset.asset_type === 'LOGO'" class="flex flex-col items-center gap-3 text-amber-200/55">
                                <PhotoIcon class="h-12 w-12" />
                                <span class="text-[10px] font-semibold uppercase tracking-[0.16em]">
                                    {{ assetPreviewLoading[asset.uuid] ? 'Chargement' : 'Aperçu indisponible' }}
                                </span>
                            </div>
                            <FilmIcon v-else-if="asset.asset_type === 'ANNOUNCEMENT_VIDEO'" class="h-14 w-14 text-amber-200/70" />
                            <p
                                v-else
                                class="line-clamp-4 px-8 text-center text-lg font-medium leading-7"
                                :style="{ color: asset.text_color, fontFamily: fontFamilies[asset.text_font] }"
                            >
                                {{ asset.text_content }}
                            </p>
                            <span class="absolute right-3 top-3 rounded-full border border-white/10 bg-slate-950/80 px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-slate-300">
                                {{ assetTypeLabel(asset.asset_type) }}
                            </span>
                        </div>

                        <div class="space-y-4 p-5">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <h2 class="text-lg font-semibold text-white">{{ asset.name }}</h2>
                                    <StatusBadge :status="asset.status" />
                                </div>
                                <p v-if="asset.file_path" class="mt-2 truncate text-xs text-slate-500">{{ asset.file_path }}</p>
                                <p v-else class="mt-2 text-xs text-slate-500">
                                    {{ fontLabels[asset.text_font] }} · {{ asset.ticker_speed }} px/s · Texte {{ asset.text_color }} · Fond {{ asset.text_background_color }}
                                </p>
                                <p v-if="asset.asset_type === 'LOGO'" class="mt-2 text-xs text-slate-500">
                                    {{ positionLabels[asset.logo_position] }} · Taille {{ asset.logo_scale }} %
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-if="asset.file_path"
                                    class="rounded-full border border-white/10 px-3 py-2 text-xs text-slate-200 hover:bg-white/5"
                                    @click="previewAsset(asset)"
                                >
                                    Apercu
                                </button>
                                <button class="rounded-full border border-white/10 px-3 py-2 text-xs text-slate-200 hover:bg-white/5" @click="openEditDialog(asset)">
                                    Modifier
                                </button>
                                <button class="rounded-full border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs text-rose-200" @click="deleteAsset(asset)">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <p v-if="errorMessage" class="rounded-[1.25rem] border border-rose-400/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-100">
            {{ errorMessage }}
        </p>

        <Dialog v-model:visible="logoDialogVisible" modal header="Ajouter un logo" :style="{ width: '34rem' }">
            <div class="space-y-5">
                <label class="block space-y-2 text-sm text-slate-700">
                    <span>Nom du logo</span>
                    <input v-model="logoForm.name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" placeholder="Logo Balafon TV">
                </label>
                <label class="block space-y-2 text-sm text-slate-700">
                    <span>Fichier PNG transparent</span>
                    <input class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" type="file" accept=".png,image/png" @change="selectLogoFile">
                </label>
                <p v-if="logoForm.file" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ logoForm.file.name }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Position a l'ecran</span>
                        <select v-model="logoForm.logo_position" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <option v-for="option in logoPositions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Taille · {{ logoForm.logo_scale }} %</span>
                        <input v-model.number="logoForm.logo_scale" class="mt-3 w-full accent-amber-500" type="range" min="10" max="50" step="5">
                    </label>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-2xl bg-slate-950">
                    <img
                        v-if="logoDraftPreviewUrl"
                        :src="logoDraftPreviewUrl"
                        alt="Aperçu du logo sélectionné"
                        class="absolute object-contain drop-shadow-[0_8px_18px_rgba(0,0,0,0.45)]"
                        :class="logoPreviewPositionClass"
                        :style="{ width: `${logoForm.logo_scale}%`, maxHeight: `${logoForm.logo_scale}%` }"
                    >
                    <div
                        v-else
                        class="absolute flex items-center justify-center rounded-lg border border-amber-300/40 bg-white/10 text-[10px] font-semibold text-white"
                        :class="logoPreviewPositionClass"
                        :style="{ width: `${logoForm.logo_scale}%`, aspectRatio: '16 / 9' }"
                    >
                        LOGO
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600" @click="logoDialogVisible = false">Annuler</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-medium text-white disabled:opacity-40" :disabled="saving || !logoForm.name.trim() || !logoForm.file" @click="saveLogo">
                        {{ saving ? 'Import...' : 'Ajouter le logo' }}
                    </button>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="announcementDialogVisible" modal header="Nouvelle bande d'annonce" :style="{ width: '40rem' }">
            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-3">
                    <button
                        class="rounded-2xl border px-4 py-4 text-left"
                        :class="announcementForm.asset_type === 'ANNOUNCEMENT_TEXT' ? 'border-amber-400 bg-amber-50 text-slate-950' : 'border-slate-200 text-slate-600'"
                        @click="announcementForm.asset_type = 'ANNOUNCEMENT_TEXT'"
                    >
                        <span class="block font-semibold">Texte defilant</span>
                        <span class="mt-1 block text-xs">Message modifiable dans Balafon</span>
                    </button>
                    <button
                        class="rounded-2xl border px-4 py-4 text-left"
                        :class="announcementForm.asset_type === 'ANNOUNCEMENT_VIDEO' ? 'border-amber-400 bg-amber-50 text-slate-950' : 'border-slate-200 text-slate-600'"
                        @click="announcementForm.asset_type = 'ANNOUNCEMENT_VIDEO'"
                    >
                        <span class="block font-semibold">Bande video</span>
                        <span class="mt-1 block text-xs">Animation importee depuis ce PC</span>
                    </button>
                </div>

                <label class="block space-y-2 text-sm text-slate-700">
                    <span>Nom</span>
                    <input v-model="announcementForm.name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" placeholder="Actualites du soir">
                </label>

                <label v-if="announcementForm.asset_type === 'ANNOUNCEMENT_TEXT'" class="block space-y-2 text-sm text-slate-700">
                    <span>Texte a faire defiler</span>
                    <textarea v-model="announcementForm.text_content" class="min-h-36 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" placeholder="Saisissez votre message..." />
                </label>

                <label v-else class="block space-y-2 text-sm text-slate-700">
                    <span>Fichier video</span>
                    <input class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" type="file" accept=".mp4,.mov,.avi,.mkv,video/*" @change="selectAnnouncementFile">
                </label>

                <div v-if="announcementForm.asset_type === 'ANNOUNCEMENT_TEXT'" class="grid gap-4 sm:grid-cols-[130px_130px_minmax(0,1fr)]">
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Couleur du texte</span>
                        <input v-model="announcementForm.text_color" class="h-12 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1" type="color">
                    </label>
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Couleur du fond</span>
                        <input v-model="announcementForm.text_background_color" class="h-12 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1" type="color">
                    </label>
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Police</span>
                        <select v-model="announcementForm.text_font" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <option v-for="option in textFonts" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                </div>
                <div
                    v-if="announcementForm.asset_type === 'ANNOUNCEMENT_TEXT'"
                    class="overflow-hidden rounded-2xl border-t-2 border-amber-500 bg-slate-950 px-5 py-5 text-xl font-bold"
                    :style="{
                        color: announcementForm.text_color,
                        backgroundColor: announcementForm.text_background_color,
                        fontFamily: fontFamilies[announcementForm.text_font],
                    }"
                >
                    {{ announcementForm.text_content || 'Apercu de votre bande d’annonce' }}
                </div>
                <label v-if="announcementForm.asset_type === 'ANNOUNCEMENT_TEXT'" class="block space-y-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                    <span>Vitesse de defilement · {{ tickerSpeedLabel(announcementForm.ticker_speed) }} · {{ announcementForm.ticker_speed }} px/s</span>
                    <input v-model.number="announcementForm.ticker_speed" class="w-full accent-amber-500" type="range" min="40" max="300" step="10">
                    <span class="flex justify-between text-xs text-slate-400"><span>Lente</span><span>Normale</span><span>Rapide</span></span>
                </label>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <input v-model="announcementForm.loop_enabled" type="checkbox">
                    <span>Repeter en boucle pendant le media</span>
                </label>

                <div class="flex justify-end gap-3">
                    <button class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600" @click="announcementDialogVisible = false">Annuler</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-medium text-white disabled:opacity-40" :disabled="!canSaveAnnouncement || saving" @click="saveAnnouncement">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer la bande' }}
                    </button>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="editDialogVisible" modal header="Modifier l'habillage" :style="{ width: '36rem' }">
            <div class="space-y-5">
                <label class="block space-y-2 text-sm text-slate-700">
                    <span>Nom</span>
                    <input v-model="editForm.name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none">
                </label>
                <label v-if="editingAsset?.asset_type === 'ANNOUNCEMENT_TEXT'" class="block space-y-2 text-sm text-slate-700">
                    <span>Texte</span>
                    <textarea v-model="editForm.text_content" class="min-h-32 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" />
                </label>
                <div v-if="editingAsset?.asset_type === 'ANNOUNCEMENT_TEXT'" class="grid gap-4 sm:grid-cols-[130px_130px_minmax(0,1fr)]">
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Texte</span>
                        <input v-model="editForm.text_color" class="h-12 w-full rounded-xl border border-slate-200 bg-white p-1" type="color">
                    </label>
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Fond</span>
                        <input v-model="editForm.text_background_color" class="h-12 w-full rounded-xl border border-slate-200 bg-white p-1" type="color">
                    </label>
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Police</span>
                        <select v-model="editForm.text_font" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
                            <option v-for="option in textFonts" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                </div>
                <label v-if="editingAsset?.asset_type === 'ANNOUNCEMENT_TEXT'" class="block space-y-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                    <span>Vitesse · {{ tickerSpeedLabel(editForm.ticker_speed) }} · {{ editForm.ticker_speed }} px/s</span>
                    <input v-model.number="editForm.ticker_speed" class="w-full accent-amber-500" type="range" min="40" max="300" step="10">
                    <span class="flex justify-between text-xs text-slate-400"><span>Lente</span><span>Normale</span><span>Rapide</span></span>
                </label>
                <div v-if="editingAsset?.asset_type === 'LOGO'" class="grid gap-4 sm:grid-cols-2">
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Position</span>
                        <select v-model="editForm.logo_position" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
                            <option v-for="option in logoPositions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <label class="block space-y-2 text-sm text-slate-700">
                        <span>Taille · {{ editForm.logo_scale }} %</span>
                        <input v-model.number="editForm.logo_scale" class="mt-3 w-full accent-amber-500" type="range" min="10" max="50" step="5">
                    </label>
                </div>
                <label class="block space-y-2 text-sm text-slate-700">
                    <span>Statut</span>
                    <select v-model="editForm.status" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="ARCHIVED">ARCHIVED</option>
                    </select>
                </label>
                <label v-if="editingAsset?.asset_type !== 'LOGO'" class="flex items-center gap-3 text-sm text-slate-700">
                    <input v-model="editForm.loop_enabled" type="checkbox">
                    <span>Repeter en boucle</span>
                </label>
                <div class="flex justify-end gap-3">
                    <button class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600" @click="editDialogVisible = false">Annuler</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-medium text-white" :disabled="saving" @click="saveEdit">Enregistrer</button>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="previewDialogVisible" modal header="Apercu" :style="{ width: '52rem' }" @hide="releasePreview">
            <div class="flex min-h-80 items-center justify-center rounded-[1.5rem] bg-slate-950 p-5">
                <img v-if="previewAssetRecord?.asset_type === 'LOGO' && previewUrl" :src="previewUrl" class="max-h-[32rem] max-w-full object-contain">
                <video v-else-if="previewUrl" :src="previewUrl" class="max-h-[32rem] w-full rounded-xl bg-black" controls autoplay loop />
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { FilmIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import Dialog from 'primevue/dialog';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useBroadcastStore } from '../../stores/broadcast';
import EmptyState from '../../ui/EmptyState.vue';
import LoadingState from '../../ui/LoadingState.vue';
import PageHeader from '../../ui/PageHeader.vue';
import StatCard from '../../ui/StatCard.vue';
import StatusBadge from '../../ui/StatusBadge.vue';

const broadcast = useBroadcastStore();
const activeTab = ref('logos');
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const logoDialogVisible = ref(false);
const announcementDialogVisible = ref(false);
const editDialogVisible = ref(false);
const previewDialogVisible = ref(false);
const editingAsset = ref(null);
const previewAssetRecord = ref(null);
const previewUrl = ref('');
const logoDraftPreviewUrl = ref('');
const assetPreviewUrls = reactive({});
const assetPreviewLoading = reactive({});

const logoPositions = [
    { value: 'TOP_RIGHT', label: 'En haut a droite' },
    { value: 'TOP_LEFT', label: 'En haut a gauche' },
    { value: 'BOTTOM_RIGHT', label: 'En bas a droite' },
    { value: 'BOTTOM_LEFT', label: 'En bas a gauche' },
];
const positionLabels = Object.fromEntries(logoPositions.map((option) => [option.value, option.label]));
const textFonts = [
    { value: 'SEGOE_UI', label: 'Segoe UI' },
    { value: 'TREBUCHET', label: 'Trebuchet MS' },
    { value: 'GEORGIA', label: 'Georgia' },
    { value: 'IMPACT', label: 'Impact' },
    { value: 'COURIER_NEW', label: 'Courier New' },
];
const fontLabels = Object.fromEntries(textFonts.map((option) => [option.value, option.label]));
const fontFamilies = {
    SEGOE_UI: '"Segoe UI", sans-serif',
    TREBUCHET: '"Trebuchet MS", sans-serif',
    GEORGIA: 'Georgia, serif',
    IMPACT: 'Impact, sans-serif',
    COURIER_NEW: '"Courier New", monospace',
};

const logoForm = reactive({
    name: '',
    file: null,
    logo_position: 'TOP_RIGHT',
    logo_scale: 20,
});
const announcementForm = reactive({
    name: '',
    asset_type: 'ANNOUNCEMENT_TEXT',
    text_content: '',
    file: null,
    loop_enabled: true,
    text_color: '#FFFFFF',
    text_background_color: '#07101D',
    text_font: 'SEGOE_UI',
    ticker_speed: 120,
});
const editForm = reactive({
    name: '',
    text_content: '',
    loop_enabled: true,
    status: 'ACTIVE',
    logo_position: 'TOP_RIGHT',
    logo_scale: 20,
    text_color: '#FFFFFF',
    text_background_color: '#07101D',
    text_font: 'SEGOE_UI',
    ticker_speed: 120,
});

const assets = computed(() => broadcast.brandingAssets);
const logos = computed(() => assets.value.filter((asset) => asset.asset_type === 'LOGO'));
const announcements = computed(() => assets.value.filter((asset) => asset.asset_type !== 'LOGO'));
const activeLogos = computed(() => logos.value.filter((asset) => asset.status === 'ACTIVE'));
const textAnnouncements = computed(() => assets.value.filter((asset) => asset.asset_type === 'ANNOUNCEMENT_TEXT' && asset.status === 'ACTIVE'));
const videoAnnouncements = computed(() => assets.value.filter((asset) => asset.asset_type === 'ANNOUNCEMENT_VIDEO' && asset.status === 'ACTIVE'));
const visibleAssets = computed(() => activeTab.value === 'logos' ? logos.value : announcements.value);
const logoPreviewPositionClass = computed(() => ({
    TOP_RIGHT: 'right-4 top-4',
    TOP_LEFT: 'left-4 top-4',
    BOTTOM_RIGHT: 'bottom-4 right-4',
    BOTTOM_LEFT: 'bottom-4 left-4',
}[logoForm.logo_position]));
const canSaveAnnouncement = computed(() => {
    if (!announcementForm.name.trim()) {
        return false;
    }

    return announcementForm.asset_type === 'ANNOUNCEMENT_TEXT'
        ? Boolean(announcementForm.text_content.trim())
        : Boolean(announcementForm.file);
});
const tickerSpeedLabel = (speed) => {
    if (speed < 90) {
        return 'Lente';
    }

    if (speed > 170) {
        return 'Rapide';
    }

    return 'Normale';
};

const resolveError = (error, fallback) => error?.response?.data?.message
    || Object.values(error?.response?.data?.errors ?? {})[0]?.[0]
    || fallback;

const load = async () => {
    loading.value = true;
    try {
        await broadcast.fetchBrandingAssets();
        await loadLogoPreviews();
    } finally {
        loading.value = false;
    }
};

const openLogoDialog = () => {
    releaseLogoDraftPreview();
    Object.assign(logoForm, {
        name: '',
        file: null,
        logo_position: 'TOP_RIGHT',
        logo_scale: 20,
    });
    errorMessage.value = '';
    logoDialogVisible.value = true;
};

const selectLogoFile = (event) => {
    releaseLogoDraftPreview();
    logoForm.file = event.target.files?.[0] ?? null;
    if (logoForm.file && !logoForm.name) {
        logoForm.name = logoForm.file.name.replace(/\.[^/.]+$/, '');
    }
    if (logoForm.file) {
        logoDraftPreviewUrl.value = URL.createObjectURL(logoForm.file);
    }
};

const saveLogo = async () => {
    saving.value = true;
    errorMessage.value = '';
    try {
        await broadcast.uploadBrandingLogo(logoForm.file, {
            name: logoForm.name.trim(),
            logo_position: logoForm.logo_position,
            logo_scale: logoForm.logo_scale,
        });
        await loadLogoPreviews();
        releaseLogoDraftPreview();
        logoDialogVisible.value = false;
    } catch (error) {
        errorMessage.value = resolveError(error, "L'import du logo a echoue.");
    } finally {
        saving.value = false;
    }
};

const openAnnouncementDialog = () => {
    Object.assign(announcementForm, {
        name: '',
        asset_type: 'ANNOUNCEMENT_TEXT',
        text_content: '',
        file: null,
        loop_enabled: true,
        text_color: '#FFFFFF',
        text_background_color: '#07101D',
        text_font: 'SEGOE_UI',
        ticker_speed: 120,
    });
    errorMessage.value = '';
    announcementDialogVisible.value = true;
};

const selectAnnouncementFile = (event) => {
    announcementForm.file = event.target.files?.[0] ?? null;
    if (announcementForm.file && !announcementForm.name) {
        announcementForm.name = announcementForm.file.name.replace(/\.[^/.]+$/, '');
    }
};

const saveAnnouncement = async () => {
    saving.value = true;
    errorMessage.value = '';
    try {
        await broadcast.createBrandingAnnouncement({
            name: announcementForm.name.trim(),
            asset_type: announcementForm.asset_type,
            text_content: announcementForm.asset_type === 'ANNOUNCEMENT_TEXT' ? announcementForm.text_content.trim() : null,
            loop_enabled: announcementForm.loop_enabled,
            text_color: announcementForm.asset_type === 'ANNOUNCEMENT_TEXT' ? announcementForm.text_color : null,
            text_background_color: announcementForm.asset_type === 'ANNOUNCEMENT_TEXT'
                ? announcementForm.text_background_color
                : null,
            text_font: announcementForm.asset_type === 'ANNOUNCEMENT_TEXT' ? announcementForm.text_font : null,
            ticker_speed: announcementForm.asset_type === 'ANNOUNCEMENT_TEXT' ? announcementForm.ticker_speed : null,
        }, announcementForm.file);
        announcementDialogVisible.value = false;
    } catch (error) {
        errorMessage.value = resolveError(error, "L'enregistrement de la bande a echoue.");
    } finally {
        saving.value = false;
    }
};

const openEditDialog = (asset) => {
    editingAsset.value = asset;
    Object.assign(editForm, {
        name: asset.name,
        text_content: asset.text_content ?? '',
        loop_enabled: asset.loop_enabled,
        status: asset.status,
        logo_position: asset.logo_position ?? 'TOP_RIGHT',
        logo_scale: asset.logo_scale ?? 20,
        text_color: asset.text_color ?? '#FFFFFF',
        text_background_color: asset.text_background_color ?? '#07101D',
        text_font: asset.text_font ?? 'SEGOE_UI',
        ticker_speed: asset.ticker_speed ?? 120,
    });
    editDialogVisible.value = true;
};

const saveEdit = async () => {
    saving.value = true;
    errorMessage.value = '';
    try {
        await broadcast.updateBrandingAsset(editingAsset.value.uuid, {
            name: editForm.name.trim(),
            text_content: editingAsset.value.asset_type === 'ANNOUNCEMENT_TEXT' ? editForm.text_content.trim() : null,
            loop_enabled: editForm.loop_enabled,
            status: editForm.status,
            logo_position: editingAsset.value.asset_type === 'LOGO' ? editForm.logo_position : null,
            logo_scale: editingAsset.value.asset_type === 'LOGO' ? editForm.logo_scale : null,
            text_color: editingAsset.value.asset_type === 'ANNOUNCEMENT_TEXT' ? editForm.text_color : null,
            text_background_color: editingAsset.value.asset_type === 'ANNOUNCEMENT_TEXT'
                ? editForm.text_background_color
                : null,
            text_font: editingAsset.value.asset_type === 'ANNOUNCEMENT_TEXT' ? editForm.text_font : null,
            ticker_speed: editingAsset.value.asset_type === 'ANNOUNCEMENT_TEXT' ? editForm.ticker_speed : null,
        });
        editDialogVisible.value = false;
    } catch (error) {
        errorMessage.value = resolveError(error, "La modification a echoue.");
    } finally {
        saving.value = false;
    }
};

const deleteAsset = async (asset) => {
    if (!window.confirm(`Supprimer "${asset.name}" ?`)) {
        return;
    }

    errorMessage.value = '';
    try {
        await broadcast.deleteBrandingAsset(asset.uuid);
        releaseAssetPreview(asset.uuid);
    } catch (error) {
        errorMessage.value = resolveError(error, "La suppression a echoue.");
    }
};

const previewAsset = async (asset) => {
    releasePreview();
    previewAssetRecord.value = asset;
    try {
        const response = await broadcast.fetchBrandingPreview(asset.uuid);
        previewUrl.value = URL.createObjectURL(response.data);
        previewDialogVisible.value = true;
    } catch (error) {
        errorMessage.value = resolveError(error, "L'apercu est indisponible.");
    }
};

const releasePreview = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = '';
};

const loadLogoPreviews = async () => {
    const currentLogos = assets.value.filter((asset) => asset.asset_type === 'LOGO' && asset.file_path);
    const currentUuids = new Set(currentLogos.map((asset) => asset.uuid));

    Object.keys(assetPreviewUrls).forEach((uuid) => {
        if (!currentUuids.has(uuid)) {
            releaseAssetPreview(uuid);
        }
    });

    await Promise.all(currentLogos.map(async (asset) => {
        if (assetPreviewUrls[asset.uuid] || assetPreviewLoading[asset.uuid]) {
            return;
        }

        assetPreviewLoading[asset.uuid] = true;
        try {
            const response = await broadcast.fetchBrandingPreview(asset.uuid);
            assetPreviewUrls[asset.uuid] = URL.createObjectURL(response.data);
        } catch {
            assetPreviewUrls[asset.uuid] = '';
        } finally {
            assetPreviewLoading[asset.uuid] = false;
        }
    }));
};

const releaseAssetPreview = (uuid) => {
    if (assetPreviewUrls[uuid]) {
        URL.revokeObjectURL(assetPreviewUrls[uuid]);
    }
    delete assetPreviewUrls[uuid];
    delete assetPreviewLoading[uuid];
};

const releaseAssetPreviews = () => {
    Object.keys(assetPreviewUrls).forEach(releaseAssetPreview);
};

const releaseLogoDraftPreview = () => {
    if (logoDraftPreviewUrl.value) {
        URL.revokeObjectURL(logoDraftPreviewUrl.value);
    }
    logoDraftPreviewUrl.value = '';
};

const assetTypeLabel = (type) => ({
    LOGO: 'Logo PNG',
    ANNOUNCEMENT_TEXT: 'Texte',
    ANNOUNCEMENT_VIDEO: 'Video',
}[type] ?? type);

onMounted(load);
onBeforeUnmount(() => {
    releasePreview();
    releaseAssetPreviews();
    releaseLogoDraftPreview();
});
</script>
