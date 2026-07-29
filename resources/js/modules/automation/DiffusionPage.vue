<template>
    <AppLayout title="Diffusion" subtitle="Pilotage du playback automatique et supervision en temps réel.">
        <PageHeader
            eyebrow="Antenne"
            title="Le direct, sans approximation."
            description="Suivez le programme en cours, la prochaine playlist, l’état vMix et les alertes d’automatisation depuis une seule régie."
        >
            <button class="rounded-full border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-200 transition hover:bg-white/[0.06]" @click="refresh">
                Refresh
            </button>
            <button class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)] transition hover:bg-amber-400" @click="tick">
                Run automation tick
            </button>
        </PageHeader>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <StatCard label="Automation state" :value="currentRun?.state ?? 'IDLE'" hint="run status" />
            <StatCard label="Current item" :value="currentRun?.current_media_asset?.title ?? 'n/a'" hint="on air asset" />
            <StatCard label="Next schedule" :value="nextScheduleLabel" hint="upcoming playlist" />
            <StatCard label="vMix" :value="controlCenter?.vmix?.connected ? 'Connected' : 'Offline'" hint="runtime bridge" />
            <StatCard label="Streaming" :value="controlCenter?.vmix?.streaming ? 'ON' : 'OFF'" hint="vmix output" />
            <StatCard label="Recent runs" :value="controlCenter?.recent_runs?.length ?? 0" hint="history window" />
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-4">
                <TimelineCard eyebrow="On Air" title="Current broadcast">
                    <div v-if="currentRun" class="space-y-4">
                        <div class="rounded-[1.75rem] border border-white/10 bg-gradient-to-br from-orange-500/15 to-transparent p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.22em] text-orange-300">{{ currentRun.schedule?.channel?.name ?? 'Channel' }}</p>
                                    <h4 class="mt-2 text-2xl font-semibold text-white">{{ currentRun.schedule?.playlist?.title ?? 'Active playlist' }}</h4>
                                    <p class="mt-2 text-sm text-slate-300">{{ currentRun.current_media_asset?.title ?? 'Waiting for media start' }}</p>
                                </div>
                                <StatusBadge :status="currentRun.state" />
                            </div>

                            <div class="mt-5">
                                <div class="mb-2 flex items-center justify-between text-xs uppercase tracking-[0.18em] text-slate-400">
                                    <span>Progression</span>
                                    <span>{{ progressLabel }}</span>
                                </div>
                                <div class="h-3 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-amber-400" :style="{ width: `${progressPercent}%` }" />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <SchedulerCard title="Current media" :meta="currentRun.current_media_asset?.media_type ?? 'n/a'">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-slate-300">{{ currentRun.current_media_asset?.title ?? 'n/a' }}</span>
                                    <span class="text-sm text-white">{{ progressLabel }}</span>
                                </div>
                            </SchedulerCard>
                            <SchedulerCard title="Next item" :meta="nextRunItem?.media_asset?.media_type ?? 'n/a'">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-slate-300">{{ nextRunItem?.media_asset?.title ?? 'No next item' }}</span>
                                    <span class="text-sm text-white">{{ nextRunItem ? formatDuration((nextRunItem.media_asset?.duration_seconds ?? 0) * 1000) : 'n/a' }}</span>
                                </div>
                            </SchedulerCard>
                        </div>

                        <div class="space-y-3">
                            <SchedulerCard
                                v-for="item in currentRun.items ?? []"
                                :key="item.uuid"
                                :title="item.media_asset?.title ?? `Item ${item.sequence}`"
                                :meta="`Sequence ${item.sequence}`"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <StatusBadge :status="item.state" />
                                    <span class="text-xs uppercase tracking-[0.18em] text-slate-500">
                                        {{ formatDuration(item.last_known_duration_ms ?? (item.media_asset?.duration_seconds ?? 0) * 1000) }}
                                    </span>
                                </div>
                            </SchedulerCard>
                        </div>
                    </div>
                    <EmptyState v-else title="No active automation run" description="When a scheduled playlist starts, this control center will show the current item, progress and the next transition." />
                </TimelineCard>
            </div>

            <div class="space-y-4">
                <TimelineCard eyebrow="Runtime" title="vMix and automation status">
                    <div class="grid gap-3">
                        <SchedulerCard title="vMix state" :meta="controlCenter?.vmix?.version ?? 'n/a'">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-slate-300">Active: {{ controlCenter?.vmix?.active_input ?? 'n/a' }}</div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-slate-300">Preview: {{ controlCenter?.vmix?.preview_input ?? 'n/a' }}</div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-slate-300">Recording: {{ controlCenter?.vmix?.recording ? 'ON' : 'OFF' }}</div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-slate-300">External: {{ controlCenter?.vmix?.external ? 'ON' : 'OFF' }}</div>
                            </div>
                        </SchedulerCard>

                        <SchedulerCard title="Next scheduled broadcast" :meta="nextScheduleTime">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-slate-300">{{ controlCenter?.next_schedule?.playlist?.title ?? 'No upcoming schedule' }}</span>
                                <StatusBadge v-if="controlCenter?.next_schedule" :status="controlCenter.next_schedule.status" />
                            </div>
                        </SchedulerCard>
                    </div>
                </TimelineCard>

                <TimelineCard eyebrow="Alerts" title="Automation alerts">
                    <div v-if="alerts.length > 0" class="space-y-3">
                        <AlertCard
                            v-for="alert in alerts"
                            :key="alert.uuid"
                            :title="alert.state"
                            :description="alert.last_error || 'Automation alert detected on a recent run.'"
                            variant="danger"
                        />
                    </div>
                    <EmptyState v-else title="No active alerts" description="No failed or skipped runs in the recent automation history." />
                </TimelineCard>

                <TimelineCard eyebrow="History" title="Recent runs">
                    <ActivityFeed :items="historyItems" />
                </TimelineCard>
            </div>
        </section>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useBroadcastStore } from '../../stores/broadcast';
import ActivityFeed from '../../ui/ActivityFeed.vue';
import AlertCard from '../../ui/AlertCard.vue';
import EmptyState from '../../ui/EmptyState.vue';
import PageHeader from '../../ui/PageHeader.vue';
import SchedulerCard from '../../ui/SchedulerCard.vue';
import StatCard from '../../ui/StatCard.vue';
import StatusBadge from '../../ui/StatusBadge.vue';
import TimelineCard from '../../ui/TimelineCard.vue';

const broadcast = useBroadcastStore();
let timer = null;

const controlCenter = computed(() => broadcast.controlCenter);
const currentRun = computed(() => controlCenter.value?.current_run ?? null);
const nextRunItem = computed(() => {
    if (!currentRun.value?.items || !currentRun.value.current_sequence) {
        return null;
    }

    return currentRun.value.items.find((item) => item.sequence === currentRun.value.current_sequence + 1) ?? null;
});
const nextScheduleLabel = computed(() => controlCenter.value?.next_schedule?.playlist?.title ?? 'No schedule');
const nextScheduleTime = computed(() => controlCenter.value?.next_schedule?.starts_at ? formatDateTime(controlCenter.value.next_schedule.starts_at) : 'n/a');
const progressPercent = computed(() => {
    if (!currentRun.value?.items || !currentRun.value.current_sequence) {
        return 0;
    }

    const currentItem = currentRun.value.items.find((item) => item.sequence === currentRun.value.current_sequence);
    const duration = currentItem?.last_known_duration_ms ?? 0;
    const position = currentItem?.last_known_position_ms ?? 0;

    if (duration <= 0) {
        return 0;
    }

    return Math.max(0, Math.min(100, Math.round((position / duration) * 100)));
});
const progressLabel = computed(() => {
    if (!currentRun.value?.items || !currentRun.value.current_sequence) {
        return '00:00 / 00:00';
    }

    const currentItem = currentRun.value.items.find((item) => item.sequence === currentRun.value.current_sequence);
    const position = currentItem?.last_known_position_ms ?? 0;
    const duration = currentItem?.last_known_duration_ms ?? 0;

    return `${formatDuration(position)} / ${formatDuration(duration)}`;
});
const alerts = computed(() => (controlCenter.value?.recent_runs ?? []).filter((run) => ['FAILED', 'SKIPPED'].includes(run.state)));
const historyItems = computed(() => (controlCenter.value?.recent_runs ?? []).map((run) => ({
    uuid: run.uuid,
    title: `${run.schedule?.playlist?.title ?? 'Playlist'} · ${run.state}`,
    subtitle: run.started_at ? formatDateTime(run.started_at) : 'Not started',
})));

const refresh = async () => {
    await broadcast.fetchControlCenter();
};

const tick = async () => {
    await broadcast.tickAutomation();
    await refresh();
};

const formatDuration = (ms) => {
    const totalSeconds = Math.max(0, Math.round((Number(ms) || 0) / 1000));
    const mins = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;

    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
};

const formatDateTime = (value) => new Date(value).toLocaleString();

onMounted(async () => {
    await refresh();
    timer = window.setInterval(refresh, 3000);
});

onBeforeUnmount(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>
