<template>
    <AppLayout title="Canaux" subtitle="Création et gestion des voies de diffusion pour la planification.">
        <PageHeader
            eyebrow="Distribution"
            title="Vos voies de diffusion."
            description="Créez et organisez les canaux utilisés pour planifier les playlists et séparer les sorties antenne."
        >
            <button class="rounded-full border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-200 transition hover:bg-white/[0.06]" @click="createNewChannel">
                Nouvelle channel
            </button>
            <button class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)] transition hover:bg-amber-400" @click="saveChannel">
                Enregistrer
            </button>
        </PageHeader>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total channels" :value="channels.length" hint="broadcast lanes" />
            <StatCard label="Active" :value="activeCount" hint="ready for planning" />
            <StatCard label="Paused" :value="pausedCount" hint="temporarily disabled" />
            <StatCard label="Timezone" :value="editor.timezone || 'Europe/Paris'" hint="selected lane" />
        </section>

        <section class="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">
            <div class="space-y-4">
                <TimelineCard eyebrow="Library" title="Configured channels">
                    <div class="space-y-3">
                        <button
                            v-for="channel in channels"
                            :key="channel.uuid"
                            class="w-full rounded-[1.5rem] border p-4 text-left transition"
                            :class="channel.uuid === selectedChannelUuid ? 'border-amber-400/40 bg-amber-500/10' : 'border-white/10 bg-slate-950/45 hover:bg-white/[0.03]'"
                            @click="selectChannel(channel)"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold text-white">{{ channel.name }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-500">{{ channel.code }}</p>
                                </div>
                                <StatusBadge :status="channel.status" />
                            </div>
                            <p class="mt-3 text-sm text-slate-400">{{ channel.description || 'No description provided.' }}</p>
                        </button>

                        <EmptyState
                            v-if="channels.length === 0"
                            title="No channel yet"
                            description="Create your first channel to unlock broadcast scheduling."
                        />
                    </div>
                </TimelineCard>
            </div>

            <div class="space-y-4">
                <TimelineCard eyebrow="Editor" title="Channel settings">
                    <template #actions>
                        <button
                            v-if="selectedChannelUuid"
                            class="rounded-full border border-rose-400/20 bg-rose-500/10 px-4 py-2 text-xs text-rose-200"
                            @click="deleteCurrentChannel"
                        >
                            Supprimer
                        </button>
                    </template>

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="space-y-2 text-sm md:col-span-2">
                                <span class="text-slate-400">Name</span>
                                <input v-model="editor.name" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none" type="text" placeholder="Studio principal">
                            </label>
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-400">Code</span>
                                <input v-model="editor.code" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none" type="text" placeholder="MAIN">
                            </label>
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-400">Timezone</span>
                                <input v-model="editor.timezone" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none" type="text" placeholder="Europe/Paris">
                            </label>
                            <label class="space-y-2 text-sm md:col-span-2">
                                <span class="text-slate-400">Description</span>
                                <textarea v-model="editor.description" class="min-h-24 w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none" />
                            </label>
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-400">Status</span>
                                <select v-model="editor.status" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 outline-none">
                                    <option value="active">active</option>
                                    <option value="paused">paused</option>
                                    <option value="archived">archived</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-[1.25rem] border border-white/10 bg-slate-950/45 p-4">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Selected code</p>
                                <p class="mt-3 text-2xl font-semibold text-white">{{ editor.code || 'n/a' }}</p>
                            </div>
                            <div class="rounded-[1.25rem] border border-white/10 bg-slate-950/45 p-4">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Planning state</p>
                                <p class="mt-3 text-2xl font-semibold capitalize text-white">{{ editor.status }}</p>
                            </div>
                        </div>
                    </div>

                    <AlertCard
                        v-if="feedback"
                        class="mt-4"
                        :title="feedback.title"
                        :description="feedback.description"
                        :variant="feedback.variant"
                    />
                </TimelineCard>

                <TimelineCard eyebrow="Usage" title="Scheduling requirement">
                    <div class="space-y-3 text-sm text-slate-300">
                        <p>Une channel represente une voie de diffusion. La planification demande une channel pour savoir ou rattacher la playlist.</p>
                        <p>Exemple simple : `MAIN`, `NEWS`, `SPORT`, ou une sortie dediee a un test local vMix.</p>
                        <RouterLink :to="{ name: 'scheduling' }" class="inline-flex rounded-full border border-white/10 px-4 py-2 text-xs text-slate-200 transition hover:bg-white/[0.04]">
                            Aller a la planification
                        </RouterLink>
                    </div>
                </TimelineCard>
            </div>
        </section>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useBroadcastStore } from '../../stores/broadcast';
import AlertCard from '../../ui/AlertCard.vue';
import EmptyState from '../../ui/EmptyState.vue';
import PageHeader from '../../ui/PageHeader.vue';
import StatCard from '../../ui/StatCard.vue';
import StatusBadge from '../../ui/StatusBadge.vue';
import TimelineCard from '../../ui/TimelineCard.vue';

const broadcast = useBroadcastStore();
const selectedChannelUuid = ref(null);
const feedback = ref(null);

const editor = reactive({
    name: '',
    code: '',
    timezone: 'Europe/Paris',
    description: '',
    status: 'active',
});

const channels = computed(() => broadcast.channels);
const activeCount = computed(() => channels.value.filter((channel) => channel.status === 'active').length);
const pausedCount = computed(() => channels.value.filter((channel) => channel.status === 'paused').length);

const load = async () => {
    await broadcast.fetchChannels();

    if (channels.value.length > 0) {
        selectChannel(channels.value[0]);
    } else {
        createNewChannel();
    }
};

const selectChannel = (channel) => {
    selectedChannelUuid.value = channel.uuid;
    editor.name = channel.name ?? '';
    editor.code = channel.code ?? '';
    editor.timezone = channel.timezone ?? 'Europe/Paris';
    editor.description = channel.description ?? '';
    editor.status = channel.status ?? 'active';
    feedback.value = null;
};

const createNewChannel = () => {
    selectedChannelUuid.value = null;
    editor.name = '';
    editor.code = '';
    editor.timezone = 'Europe/Paris';
    editor.description = '';
    editor.status = 'active';
    feedback.value = null;
};

const saveChannel = async () => {
    feedback.value = null;

    if (!editor.name.trim() || !editor.code.trim() || !editor.timezone.trim()) {
        feedback.value = {
            title: 'Missing data',
            description: 'Fill in name, code and timezone before saving the channel.',
            variant: 'warning',
        };
        return;
    }

    const payload = {
        name: editor.name.trim(),
        code: editor.code.trim(),
        timezone: editor.timezone.trim(),
        description: editor.description.trim() || null,
        status: editor.status,
    };

    try {
        const channel = selectedChannelUuid.value
            ? await broadcast.updateChannel(selectedChannelUuid.value, payload)
            : await broadcast.createChannel(payload);

        const fresh = broadcast.channels.find((entry) => entry.uuid === channel.uuid) ?? channel;
        selectChannel(fresh);

        feedback.value = {
            title: 'Channel saved',
            description: 'The broadcast channel is available for scheduling.',
            variant: 'success',
        };
    } catch (error) {
        feedback.value = {
            title: 'Save failed',
            description: error?.response?.data?.message ?? 'The channel could not be saved.',
            variant: 'danger',
        };
    }
};

const deleteCurrentChannel = async () => {
    if (!selectedChannelUuid.value) {
        return;
    }

    try {
        await broadcast.deleteChannel(selectedChannelUuid.value);

        if (broadcast.channels.length > 0) {
            selectChannel(broadcast.channels[0]);
        } else {
            createNewChannel();
        }

        feedback.value = {
            title: 'Channel deleted',
            description: 'The channel has been removed.',
            variant: 'success',
        };
    } catch (error) {
        feedback.value = {
            title: 'Delete failed',
            description: error?.response?.data?.message ?? 'The channel could not be deleted.',
            variant: 'danger',
        };
    }
};

onMounted(load);
</script>
