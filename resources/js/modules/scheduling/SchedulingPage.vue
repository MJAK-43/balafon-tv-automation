<template>
    <AppLayout title="Planification" subtitle="Organisation de la grille, duplication des jours et contrôle des chevauchements.">
        <PageHeader
            eyebrow="Grille antenne"
            title="Chaque programme, au bon moment."
            description="Construisez votre grille en vue jour ou semaine et détectez les conflits avant leur passage à l’antenne."
        >
            <button class="rounded-full border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-200 transition hover:bg-white/[0.06]" @click="toggleView">
                {{ currentView === 'day' ? 'Vue semaine' : 'Vue du jour' }}
            </button>
            <button class="rounded-full border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-200 transition hover:bg-white/[0.06]" @click="runDuplicateDay">
                Dupliquer le jour
            </button>
            <button class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)] transition hover:bg-amber-400" @click="runDuplicateWeek">
                Dupliquer la semaine
            </button>
        </PageHeader>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Diffusions" :value="visibleSchedules.length" hint="dans la vue actuelle" />
            <StatCard label="Conflits" :value="visibleConflicts.length" hint="chevauchements détectés" />
            <StatCard label="Canal sélectionné" :value="selectedChannel?.code ?? 'n/a'" hint="grille active" />
            <StatCard label="Période" :value="currentView === 'day' ? selectedDate : `${weekRange.start} au ${weekRange.end}`" hint="fenêtre affichée" />
        </section>

        <section class="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">
            <div class="space-y-4">
                <FilterPanel title="Planning Controls" @reset="resetPlanningFilters">
                    <div class="space-y-2">
                        <label class="text-xs uppercase tracking-[0.24em] text-slate-500">Channel</label>
                        <select v-model="selectedChannelId" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white outline-none">
                            <option v-for="channel in channels" :key="channel.id" :value="channel.id">{{ channel.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs uppercase tracking-[0.24em] text-slate-500">Date</label>
                        <input v-model="selectedDate" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white outline-none" type="date">
                    </div>
                </FilterPanel>

                <TimelineCard eyebrow="Quick schedule" title="Plan manually">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-xs uppercase tracking-[0.24em] text-slate-500">Playlist</label>
                            <select v-model="scheduleForm.playlistId" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white outline-none">
                                <option :value="null" disabled>Select a playlist</option>
                                <option v-for="playlist in readyPlaylists" :key="playlist.id" :value="playlist.id">{{ playlist.title }}</option>
                            </select>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-xs uppercase tracking-[0.24em] text-slate-500">Broadcast date</label>
                                <input v-model="scheduleForm.date" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white outline-none" type="date">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs uppercase tracking-[0.24em] text-slate-500">Start time</label>
                                <input v-model="scheduleForm.time" class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white outline-none" type="time" step="60">
                            </div>
                        </div>
                        <div class="rounded-[1.5rem] border border-white/10 bg-slate-950/50 p-4 text-sm text-slate-300">
                            <div class="flex items-center justify-between gap-3">
                                <span>Selected channel</span>
                                <span class="font-medium text-white">{{ selectedChannel?.name ?? 'n/a' }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span>Machine timezone</span>
                                <span class="font-medium text-white">{{ operatorTimezone }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span>Playlist duration</span>
                                <span class="font-medium text-white">{{ selectedPlaylistDuration }}</span>
                            </div>
                        </div>
                        <AlertCard
                            v-if="scheduleFeedback"
                            :title="scheduleFeedback.title"
                            :description="scheduleFeedback.description"
                            :variant="scheduleFeedback.variant"
                        />
                        <button
                            class="w-full rounded-2xl bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isScheduling"
                            @click="submitManualSchedule"
                        >
                            {{ isScheduling ? 'Scheduling...' : 'Schedule on calendar' }}
                        </button>
                    </div>
                </TimelineCard>

                <div v-if="visibleConflicts.length > 0" class="space-y-3">
                    <AlertCard
                        v-for="conflict in visibleConflicts"
                        :key="`${conflict.schedule_uuid}-${conflict.related_schedule_uuid}`"
                        title="Conflict detected"
                        :description="conflict.message"
                        variant="danger"
                    />
                </div>

                <TimelineCard eyebrow="Playlists" title="Drop source">
                    <div class="space-y-3">
                        <div
                            v-for="playlist in readyPlaylists"
                            :key="playlist.uuid"
                            draggable="true"
                            class="rounded-[1.5rem] border border-white/10 bg-slate-950/50 p-4"
                            @dragstart="onPlaylistDragStart(playlist)"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-white">{{ playlist.title }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-500">{{ formatDuration(playlist.total_duration_seconds ?? 0) }}</p>
                                </div>
                                <StatusBadge :status="playlist.status" />
                            </div>
                        </div>
                    </div>
                </TimelineCard>
            </div>

            <div class="space-y-4">
                <TimelineCard eyebrow="Grille de diffusion" :title="currentView === 'day' ? 'Calendrier du jour' : 'Calendrier de la semaine'">
                    <template #actions>
                        <button class="rounded-full border border-white/10 px-4 py-2 text-xs text-slate-300" @click="refreshTimeline">
                            Actualiser
                        </button>
                    </template>

                    <div v-if="!selectedChannel">
                        <div class="space-y-4">
                            <EmptyState title="No channel available" description="Create a channel first, then return to scheduling." />
                            <RouterLink :to="{ name: 'channels' }" class="inline-flex rounded-full border border-white/10 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/[0.04]">
                                Create a channel
                            </RouterLink>
                        </div>
                    </div>

                    <div v-else-if="currentView === 'day'" class="broadcast-calendar">
                        <div class="calendar-toolbar">
                            <button class="calendar-nav" type="button" aria-label="Jour precedent" @click="shiftSelectedDate(-1)">
                                &larr;
                            </button>
                            <div class="calendar-toolbar__date">
                                <p>{{ formattedSelectedDate }}</p>
                                <span>{{ selectedChannel.name }} / {{ operatorTimezone }}</span>
                            </div>
                            <button v-if="!isSelectedDateToday" class="calendar-today" type="button" @click="goToToday">
                                Aujourd'hui
                            </button>
                            <button class="calendar-nav" type="button" aria-label="Jour suivant" @click="shiftSelectedDate(1)">
                                &rarr;
                            </button>
                        </div>

                        <div class="calendar-overview">
                            <div>
                                <span>Programmes</span>
                                <strong>{{ sortedDaySchedules.length }}</strong>
                            </div>
                            <div>
                                <span>Durée cumulée</span>
                                <strong>{{ dayTotalDuration }}</strong>
                            </div>
                            <div>
                                <span>Première diffusion</span>
                                <strong>{{ firstDaySchedule ? formatTime(firstDaySchedule.starts_at) : '--:--' }}</strong>
                            </div>
                            <div>
                                <span>Fin de grille</span>
                                <strong>{{ lastDaySchedule ? formatTime(lastDaySchedule.ends_at) : '--:--' }}</strong>
                            </div>
                        </div>

                        <div v-if="sortedDaySchedules.length === 0" class="calendar-empty">
                            <span class="calendar-empty__index">00</span>
                            <div>
                                <h3>Aucune diffusion ce jour</h3>
                                <p>Programmez une playlist depuis le panneau de gauche ou déposez-la dans la zone ci-dessous.</p>
                            </div>
                        </div>

                        <div v-else class="agenda-list">
                            <article
                                v-for="(schedule, index) in sortedDaySchedules"
                                :key="schedule.uuid"
                                draggable="true"
                                class="agenda-row"
                                :class="{
                                    'agenda-row--conflict': scheduleHasConflict(schedule.uuid),
                                    'agenda-row--live': scheduleIsCurrent(schedule),
                                }"
                                @dragstart="onScheduleDragStart(schedule)"
                            >
                                <div class="agenda-time">
                                    <strong>{{ formatTime(schedule.starts_at) }}</strong>
                                    <span>{{ formatTime(schedule.ends_at) }}</span>
                                </div>
                                <div class="agenda-rail" aria-hidden="true">
                                    <span>{{ String(index + 1).padStart(2, '0') }}</span>
                                </div>
                                <div class="agenda-card">
                                    <div class="agenda-card__main">
                                        <div class="agenda-card__title">
                                            <span v-if="scheduleIsCurrent(schedule)" class="on-air-label">En direct</span>
                                            <span v-else-if="scheduleHasConflict(schedule.uuid)" class="conflict-label">Conflit</span>
                                            <p>{{ schedule.playlist?.title ?? 'Playlist sans titre' }}</p>
                                        </div>
                                        <div class="agenda-card__meta">
                                            <span>{{ selectedChannel.code }}</span>
                                            <span>{{ formatScheduleDuration(schedule) }}</span>
                                            <span>Début {{ formatTime(schedule.starts_at) }}</span>
                                        </div>
                                    </div>
                                    <div class="agenda-card__actions">
                                        <StatusBadge :status="schedule.status" />
                                        <div class="duration-controls" aria-label="Ajuster la durée">
                                            <button type="button" @click.stop="resizeSchedule(schedule, -15)">-15 min</button>
                                            <button type="button" @click.stop="resizeSchedule(schedule, 15)">+15 min</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="calendar-dropzone" @dragover.prevent @drop="onAgendaDrop">
                            <div>
                                <strong>Déposer une playlist ici</strong>
                                <span>La diffusion sera placée à {{ scheduleForm.time }} sur cette date.</span>
                            </div>
                            <span class="calendar-dropzone__time">{{ scheduleForm.time }}</span>
                        </div>
                    </div>

                    <div v-else class="week-calendar">
                        <div
                            v-for="day in weekDays"
                            :key="day.date"
                            class="week-day"
                        >
                            <div class="week-day__header">
                                <div>
                                    <p>{{ day.label }}</p>
                                    <span>{{ schedulesForDate(day.date).length }} programme(s)</span>
                                </div>
                                <button type="button" @click="selectedDate = day.date; currentView = 'day'">Ouvrir</button>
                            </div>
                            <div v-if="schedulesForDate(day.date).length" class="space-y-2">
                                <SchedulerCard
                                    v-for="schedule in schedulesForDate(day.date)"
                                    :key="schedule.uuid"
                                    :title="schedule.playlist.title"
                                    :meta="`${formatTime(schedule.starts_at)} - ${formatTime(schedule.ends_at)} / ${formatScheduleDuration(schedule)}`"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <StatusBadge :status="schedule.status" />
                                        <span v-if="scheduleHasConflict(schedule.uuid)" class="text-xs uppercase tracking-[0.18em] text-rose-300">Conflit</span>
                                    </div>
                                </SchedulerCard>
                            </div>
                            <p v-else class="week-day__empty">Aucune diffusion planifiée.</p>
                        </div>
                    </div>
                </TimelineCard>
            </div>
        </section>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import AppLayout from '../../layouts/AppLayout.vue';
import { useBroadcastStore } from '../../stores/broadcast';
import AlertCard from '../../ui/AlertCard.vue';
import EmptyState from '../../ui/EmptyState.vue';
import FilterPanel from '../../ui/FilterPanel.vue';
import PageHeader from '../../ui/PageHeader.vue';
import SchedulerCard from '../../ui/SchedulerCard.vue';
import StatCard from '../../ui/StatCard.vue';
import StatusBadge from '../../ui/StatusBadge.vue';
import TimelineCard from '../../ui/TimelineCard.vue';

const broadcast = useBroadcastStore();
const currentView = ref('day');
const selectedChannelId = ref(null);
const operatorTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
const now = new Date();
const selectedDate = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`);
const draggedPlaylist = ref(null);
const draggedSchedule = ref(null);
const scheduleForm = ref({
    playlistId: null,
    date: selectedDate.value,
    time: '08:00',
});
const scheduleFeedback = ref(null);
const isScheduling = ref(false);

const channels = computed(() => broadcast.channels);
const readyPlaylists = computed(() => broadcast.playlists.filter((playlist) => playlist.status !== 'ARCHIVED'));
const schedules = computed(() => broadcast.schedules);
const selectedChannel = computed(() => channels.value.find((channel) => channel.id === Number(selectedChannelId.value)) ?? null);
const selectedFormPlaylist = computed(() => readyPlaylists.value.find((playlist) => playlist.id === Number(scheduleForm.value.playlistId)) ?? null);
const selectedPlaylistDuration = computed(() => formatDuration(selectedFormPlaylist.value?.total_duration_seconds ?? 0));
const daySchedules = computed(() => visibleSchedules.value.filter((schedule) => schedule.channel_id === Number(selectedChannelId.value)));
const sortedDaySchedules = computed(() => [...daySchedules.value].sort(
    (first, second) => new Date(first.starts_at).getTime() - new Date(second.starts_at).getTime(),
));
const firstDaySchedule = computed(() => sortedDaySchedules.value[0] ?? null);
const lastDaySchedule = computed(() => sortedDaySchedules.value.at(-1) ?? null);
const dayTotalDuration = computed(() => formatDuration(
    sortedDaySchedules.value.reduce((total, schedule) => total + scheduleDurationSeconds(schedule), 0),
));
const formattedSelectedDate = computed(() => new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
}).format(new Date(`${selectedDate.value}T12:00:00`)));
const isSelectedDateToday = computed(() => selectedDate.value === todayInTimezone(operatorTimezone));
const visibleSchedules = computed(() => schedules.value.filter((schedule) => {
    if (Number(selectedChannelId.value) && schedule.channel_id !== Number(selectedChannelId.value)) {
        return false;
    }

    if (currentView.value === 'day') {
        return dateKeyInTimezone(schedule.starts_at, operatorTimezone) === selectedDate.value;
    }

    const scheduleDate = dateKeyInTimezone(schedule.starts_at, operatorTimezone);

    return scheduleDate >= weekRange.value.start
        && scheduleDate <= weekRange.value.end;
}));
const visibleConflicts = computed(() => broadcast.conflicts.filter((conflict) => {
    const schedule = visibleSchedules.value.find((item) => item.uuid === conflict.schedule_uuid || item.uuid === conflict.related_schedule_uuid);

    return Boolean(schedule);
}));
const weekRange = computed(() => {
    const base = new Date(`${selectedDate.value}T00:00:00`);
    const day = base.getDay() || 7;
    const monday = new Date(base);
    monday.setDate(base.getDate() - day + 1);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    return {
        start: formatLocalDate(monday),
        end: formatLocalDate(sunday),
    };
});
const weekDays = computed(() => Array.from({ length: 7 }, (_, index) => {
    const date = new Date(`${weekRange.value.start}T00:00:00`);
    date.setDate(date.getDate() + index);

    return {
        date: formatLocalDate(date),
        label: date.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'short' }),
    };
}));

const load = async () => {
    await Promise.all([
        broadcast.fetchChannels(),
        broadcast.fetchPlaylists(),
    ]);

    if (!selectedChannelId.value && channels.value.length > 0) {
        selectedChannelId.value = channels.value[0].id;
        selectedDate.value = todayInTimezone(operatorTimezone);
        scheduleForm.value.playlistId = readyPlaylists.value[0]?.id ?? null;
        scheduleForm.value.date = selectedDate.value;
    }

    await refreshTimeline();
};

const refreshTimeline = async () => {
    if (!selectedChannelId.value) {
        return;
    }

    const filters = currentView.value === 'day'
        ? { channel_id: selectedChannelId.value, date: selectedDate.value, timezone: operatorTimezone }
        : { channel_id: selectedChannelId.value, date_from: weekRange.value.start, date_to: weekRange.value.end, timezone: operatorTimezone };

    await Promise.all([
        broadcast.fetchSchedules(filters),
        broadcast.fetchConflicts(filters),
    ]);
};

const toggleView = async () => {
    currentView.value = currentView.value === 'day' ? 'week' : 'day';
    await refreshTimeline();
};

const resetPlanningFilters = async () => {
    if (channels.value.length > 0) {
        selectedChannelId.value = channels.value[0].id;
        selectedDate.value = todayInTimezone(operatorTimezone);
    }
    scheduleFeedback.value = null;
    scheduleForm.value.date = selectedDate.value;
    currentView.value = 'day';
    await refreshTimeline();
};

const submitManualSchedule = async () => {
    scheduleFeedback.value = null;

    if (!selectedChannel.value) {
        scheduleFeedback.value = {
            title: 'Channel required',
            description: 'Select a broadcast channel before scheduling a playlist.',
            variant: 'danger',
        };
        return;
    }

    if (!scheduleForm.value.playlistId || !scheduleForm.value.date || !scheduleForm.value.time) {
        scheduleFeedback.value = {
            title: 'Missing planning data',
            description: 'Choose a playlist, a date and a start time before validating.',
            variant: 'warning',
        };
        return;
    }

    isScheduling.value = true;

    try {
        const startsAt = localDateTimeString(scheduleForm.value.date, scheduleForm.value.time);

        await broadcast.createSchedule({
            channel_id: selectedChannel.value.id,
            playlist_id: Number(scheduleForm.value.playlistId),
            starts_at: startsAt,
            ends_at: null,
            status: 'SCHEDULED',
            timezone: operatorTimezone,
        });

        selectedDate.value = scheduleForm.value.date;
        currentView.value = 'day';
        await refreshTimeline();

        scheduleFeedback.value = {
            title: 'Schedule created',
            description: `The playlist is now scheduled for ${scheduleForm.value.date} at ${scheduleForm.value.time} and visible on the calendar.`,
            variant: 'success',
        };
    } catch (error) {
        scheduleFeedback.value = {
            title: 'Scheduling failed',
            description: error?.response?.data?.message ?? 'The schedule could not be created. Check the playlist and time, then try again.',
            variant: 'danger',
        };
    } finally {
        isScheduling.value = false;
    }
};

const onPlaylistDragStart = (playlist) => {
    draggedPlaylist.value = playlist;
    draggedSchedule.value = null;
};

const onScheduleDragStart = (schedule) => {
    draggedSchedule.value = schedule;
    draggedPlaylist.value = null;
};

const onAgendaDrop = async () => {
    if (!selectedChannel.value || (!draggedPlaylist.value && !draggedSchedule.value)) {
        return;
    }

    const startsAt = localDateTimeString(selectedDate.value, scheduleForm.value.time || '08:00');

    if (draggedPlaylist.value) {
        await broadcast.createSchedule({
            channel_id: selectedChannel.value.id,
            playlist_id: draggedPlaylist.value.id,
            starts_at: startsAt,
            ends_at: null,
            status: 'SCHEDULED',
            timezone: operatorTimezone,
        });
    }

    if (draggedSchedule.value) {
        const previousStart = new Date(localDateTimeFromValue(draggedSchedule.value.starts_at, operatorTimezone).replace(' ', 'T'));
        const nextStart = new Date(startsAt.replace(' ', 'T'));
        const deltaMinutes = Math.round((nextStart.getTime() - previousStart.getTime()) / 60000);

        await broadcast.updateSchedule(draggedSchedule.value.uuid, {
            channel_id: draggedSchedule.value.channel_id,
            playlist_id: draggedSchedule.value.playlist_id,
            starts_at: startsAt,
            ends_at: draggedSchedule.value.ends_at
                ? shiftLocalDateTime(draggedSchedule.value.ends_at, deltaMinutes, operatorTimezone)
                : null,
            status: draggedSchedule.value.status,
            timezone: operatorTimezone,
        });
    }

    draggedPlaylist.value = null;
    draggedSchedule.value = null;
    await refreshTimeline();
};

const resizeSchedule = async (schedule, deltaMinutes) => {
    if (!schedule.ends_at) {
        return;
    }

    const shiftedEnd = shiftLocalDateTime(schedule.ends_at, deltaMinutes, operatorTimezone);
    const currentStart = new Date(localDateTimeFromValue(schedule.starts_at, operatorTimezone).replace(' ', 'T'));
    const nextEnd = new Date(shiftedEnd.replace(' ', 'T'));

    if (nextEnd <= currentStart) {
        return;
    }

    await broadcast.updateSchedule(schedule.uuid, {
        channel_id: schedule.channel_id,
        playlist_id: schedule.playlist_id,
        starts_at: localDateTimeFromValue(schedule.starts_at, operatorTimezone),
        ends_at: shiftedEnd,
        status: schedule.status,
        timezone: operatorTimezone,
    });
    await refreshTimeline();
};

const runDuplicateDay = async () => {
    if (!selectedChannelId.value) {
        return;
    }

    const target = new Date(`${selectedDate.value}T00:00:00`);
    target.setDate(target.getDate() + 1);

    await broadcast.duplicateDay({
        channel_id: selectedChannelId.value,
        source_date: selectedDate.value,
        target_date: formatLocalDate(target),
        timezone: operatorTimezone,
    });
    await refreshTimeline();
};

const runDuplicateWeek = async () => {
    if (!selectedChannelId.value) {
        return;
    }

    const source = new Date(`${weekRange.value.start}T00:00:00`);
    const target = new Date(source);
    target.setDate(target.getDate() + 7);

    await broadcast.duplicateWeek({
        channel_id: selectedChannelId.value,
        source_week_start: weekRange.value.start,
        target_week_start: formatLocalDate(target),
        timezone: operatorTimezone,
    });
    await refreshTimeline();
};

const scheduleHasConflict = (uuid) => visibleConflicts.value.some((conflict) => conflict.schedule_uuid === uuid || conflict.related_schedule_uuid === uuid);

const scheduleDurationSeconds = (schedule) => {
    if (schedule.starts_at && schedule.ends_at) {
        const duration = Math.round((new Date(schedule.ends_at).getTime() - new Date(schedule.starts_at).getTime()) / 1000);

        if (Number.isFinite(duration) && duration > 0) {
            return duration;
        }
    }

    return Number(schedule.playlist?.total_duration_seconds ?? 0);
};

const formatScheduleDuration = (schedule) => formatDuration(scheduleDurationSeconds(schedule));

const scheduleIsCurrent = (schedule) => {
    if (!schedule.starts_at || !schedule.ends_at) {
        return false;
    }

    const current = Date.now();

    return current >= new Date(schedule.starts_at).getTime()
        && current < new Date(schedule.ends_at).getTime();
};

const shiftSelectedDate = (days) => {
    const date = new Date(`${selectedDate.value}T12:00:00`);
    date.setDate(date.getDate() + days);
    selectedDate.value = formatLocalDate(date);
};

const goToToday = () => {
    selectedDate.value = todayInTimezone(operatorTimezone);
};

const schedulesForDate = (date) => visibleSchedules.value.filter((schedule) => dateKeyInTimezone(schedule.starts_at, operatorTimezone) === date);

const getFormatter = (timeZone) => new Intl.DateTimeFormat('en-CA', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
});

const formatParts = (value, timeZone) => Object.fromEntries(
    getFormatter(timeZone)
        .formatToParts(new Date(value))
        .filter(({ type }) => type !== 'literal')
        .map(({ type, value: partValue }) => [type, partValue]),
);

const dateKeyInTimezone = (value, timeZone) => {
    const parts = formatParts(value, timeZone);

    return `${parts.year}-${parts.month}-${parts.day}`;
};

const timePartsInTimezone = (value, timeZone) => {
    const parts = formatParts(value, timeZone);

    return {
        hours: Number(parts.hour),
        minutes: Number(parts.minute),
    };
};

const localDateTimeString = (date, time) => `${date} ${time}:00`;

const formatLocalDate = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const localDateTimeFromValue = (value, timeZone) => {
    const parts = formatParts(value, timeZone);

    return `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute}:${parts.second}`;
};

const shiftLocalDateTime = (value, deltaMinutes, timeZone) => {
    const localized = localDateTimeFromValue(value, timeZone).replace(' ', 'T');
    const shifted = new Date(localized);
    shifted.setMinutes(shifted.getMinutes() + deltaMinutes);

    return `${shifted.getFullYear()}-${String(shifted.getMonth() + 1).padStart(2, '0')}-${String(shifted.getDate()).padStart(2, '0')} ${String(shifted.getHours()).padStart(2, '0')}:${String(shifted.getMinutes()).padStart(2, '0')}:00`;
};

const formatTime = (value) => {
    if (!value) {
        return '--:--';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: operatorTimezone,
    }).format(new Date(value));
};

const todayInTimezone = (timeZone) => dateKeyInTimezone(new Date().toISOString(), timeZone);
const formatDuration = (seconds) => {
    const safe = Number(seconds ?? 0);
    const hrs = Math.floor(safe / 3600);
    const mins = Math.floor((safe % 3600) / 60);
    const secs = safe % 60;

    return [hrs, mins, secs].map((entry) => String(entry).padStart(2, '0')).join(':');
};

watch([selectedChannelId, selectedDate], async () => {
    await refreshTimeline();
});

watch(selectedChannelId, (value) => {
    const channel = channels.value.find((entry) => entry.id === Number(value));

    if (channel) {
        selectedDate.value = todayInTimezone(operatorTimezone);
    }
});

watch(selectedDate, (value) => {
    scheduleForm.value.date = value;
});

watch(readyPlaylists, (playlists) => {
    if (!scheduleForm.value.playlistId && playlists.length > 0) {
        scheduleForm.value.playlistId = playlists[0].id;
    }
}, { immediate: true });

onMounted(load);
</script>

<style scoped>
.broadcast-calendar {
    display: grid;
    gap: 18px;
}

.calendar-toolbar {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto 44px;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 17px;
    background:
        radial-gradient(circle at 20% 0%, rgba(255, 77, 46, 0.11), transparent 34%),
        var(--ink-1);
}

.calendar-nav,
.calendar-today {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--line);
    border-radius: 12px;
    color: var(--paper);
    background: rgba(255, 255, 255, 0.035);
    transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
}

.calendar-nav {
    width: 44px;
    font-size: 1.15rem;
}

.calendar-today {
    padding: 0 15px;
    color: var(--signal-bright);
    font-size: 0.76rem;
    font-weight: 700;
}

.calendar-nav:hover,
.calendar-today:hover {
    border-color: var(--line-strong);
    background: rgba(255, 255, 255, 0.07);
    transform: translateY(-1px);
}

.calendar-toolbar__date {
    min-width: 0;
    padding: 0 6px;
}

.calendar-toolbar__date p {
    overflow: hidden;
    margin: 0;
    color: var(--paper);
    font-size: clamp(1rem, 2vw, 1.3rem);
    font-weight: 650;
    letter-spacing: -0.025em;
    text-overflow: ellipsis;
    text-transform: capitalize;
    white-space: nowrap;
}

.calendar-toolbar__date span {
    display: block;
    margin-top: 3px;
    color: var(--muted);
    font-size: 0.72rem;
}

.calendar-overview {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 17px;
    background: rgba(255, 255, 255, 0.018);
}

.calendar-overview > div {
    display: grid;
    gap: 7px;
    min-width: 0;
    padding: 15px 17px;
}

.calendar-overview > div + div {
    border-left: 1px solid var(--line);
}

.calendar-overview span {
    overflow: hidden;
    color: var(--muted);
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}

.calendar-overview strong {
    color: var(--paper);
    font-size: 1.08rem;
    font-variant-numeric: tabular-nums;
    font-weight: 650;
}

.agenda-list {
    display: grid;
    padding: 6px 0;
}

.agenda-row {
    display: grid;
    grid-template-columns: 78px 44px minmax(0, 1fr);
    min-width: 0;
    cursor: grab;
}

.agenda-row:active {
    cursor: grabbing;
}

.agenda-time {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    padding: 20px 0;
    font-variant-numeric: tabular-nums;
}

.agenda-time strong {
    color: var(--paper);
    font-size: 0.9rem;
    font-weight: 700;
}

.agenda-time span {
    color: var(--muted);
    font-size: 0.68rem;
}

.agenda-rail {
    position: relative;
    display: flex;
    justify-content: center;
}

.agenda-rail::before {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 50%;
    width: 1px;
    background: var(--line);
    content: '';
}

.agenda-rail span {
    position: relative;
    z-index: 1;
    display: inline-flex;
    width: 27px;
    height: 27px;
    align-items: center;
    justify-content: center;
    margin-top: 17px;
    border: 1px solid var(--line-strong);
    border-radius: 50%;
    color: var(--muted);
    background: var(--ink-1);
    font-size: 0.56rem;
    font-weight: 750;
    letter-spacing: 0.06em;
}

.agenda-card {
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin: 6px 0 10px;
    padding: 17px 18px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background:
        linear-gradient(100deg, rgba(255, 255, 255, 0.045), transparent 48%),
        var(--ink-2);
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.16);
    transition: border-color 160ms ease, transform 160ms ease, background 160ms ease;
}

.agenda-row:hover .agenda-card {
    border-color: var(--line-strong);
    transform: translateX(2px);
}

.agenda-row--live .agenda-card {
    border-color: rgba(184, 244, 92, 0.38);
    background:
        linear-gradient(100deg, rgba(184, 244, 92, 0.11), transparent 46%),
        var(--ink-2);
}

.agenda-row--live .agenda-rail span {
    border-color: rgba(184, 244, 92, 0.48);
    color: var(--ink-0);
    background: var(--live);
}

.agenda-row--conflict .agenda-card {
    border-color: rgba(255, 92, 117, 0.42);
    background:
        linear-gradient(100deg, rgba(255, 92, 117, 0.11), transparent 46%),
        var(--ink-2);
}

.agenda-card__main {
    min-width: 0;
}

.agenda-card__title {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
}

.agenda-card__title p {
    overflow: hidden;
    margin: 0;
    color: var(--paper);
    font-size: 1rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.on-air-label,
.conflict-label {
    flex: 0 0 auto;
    padding: 4px 7px;
    border-radius: 5px;
    font-size: 0.52rem;
    font-weight: 800;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.on-air-label {
    color: var(--ink-0);
    background: var(--live);
}

.conflict-label {
    color: #ffd9df;
    background: rgba(255, 92, 117, 0.2);
}

.agenda-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px 15px;
    margin-top: 8px;
    color: var(--muted);
    font-size: 0.65rem;
    font-weight: 650;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.agenda-card__meta span {
    position: relative;
}

.agenda-card__meta span + span::before {
    position: absolute;
    top: 50%;
    left: -9px;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: var(--signal);
    content: '';
    transform: translateY(-50%);
}

.agenda-card__actions {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 13px;
}

.duration-controls {
    display: flex;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 9px;
}

.duration-controls button {
    min-height: 32px;
    padding: 0 10px;
    color: var(--muted);
    background: rgba(255, 255, 255, 0.025);
    font-size: 0.65rem;
    font-weight: 650;
    transition: color 160ms ease, background 160ms ease;
}

.duration-controls button + button {
    border-left: 1px solid var(--line);
}

.duration-controls button:hover {
    color: var(--paper);
    background: rgba(255, 255, 255, 0.07);
}

.calendar-dropzone {
    display: flex;
    min-height: 76px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 15px 18px;
    border: 1px dashed rgba(255, 106, 77, 0.42);
    border-radius: 16px;
    color: var(--muted);
    background: rgba(255, 77, 46, 0.035);
    transition: border-color 160ms ease, background 160ms ease;
}

.calendar-dropzone:hover {
    border-color: var(--signal-bright);
    background: rgba(255, 77, 46, 0.07);
}

.calendar-dropzone strong,
.calendar-dropzone span {
    display: block;
}

.calendar-dropzone strong {
    color: var(--paper);
    font-size: 0.82rem;
}

.calendar-dropzone div > span {
    margin-top: 4px;
    font-size: 0.68rem;
}

.calendar-dropzone__time {
    color: var(--signal-bright);
    font-size: 1.15rem;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}

.calendar-empty {
    display: flex;
    min-height: 170px;
    align-items: center;
    gap: 20px;
    padding: 28px;
    border: 1px dashed var(--line-strong);
    border-radius: 17px;
    background: rgba(255, 255, 255, 0.018);
}

.calendar-empty__index {
    color: rgba(244, 240, 231, 0.12);
    font-size: 3.6rem;
    font-weight: 800;
    letter-spacing: -0.08em;
}

.calendar-empty h3 {
    margin: 0;
    color: var(--paper);
    font-size: 1rem;
}

.calendar-empty p {
    max-width: 520px;
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 0.78rem;
    line-height: 1.55;
}

.week-calendar {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.week-day {
    min-width: 0;
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 17px;
    background: rgba(255, 255, 255, 0.018);
}

.week-day__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.week-day__header p {
    margin: 0;
    color: var(--paper);
    font-size: 0.92rem;
    font-weight: 650;
    text-transform: capitalize;
}

.week-day__header span,
.week-day__empty {
    color: var(--muted);
    font-size: 0.65rem;
}

.week-day__header span {
    display: block;
    margin-top: 3px;
}

.week-day__header button {
    padding: 6px 10px;
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--signal-bright);
    background: transparent;
    font-size: 0.65rem;
}

.week-day__empty {
    margin: 0;
    padding: 24px 4px;
    text-align: center;
}

@media (max-width: 760px) {
    .calendar-toolbar {
        grid-template-columns: 40px minmax(0, 1fr) 40px;
    }

    .calendar-today {
        grid-column: 2;
        grid-row: 2;
        justify-self: start;
        min-height: 32px;
    }

    .calendar-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .calendar-overview > div:nth-child(3) {
        border-top: 1px solid var(--line);
        border-left: 0;
    }

    .calendar-overview > div:nth-child(4) {
        border-top: 1px solid var(--line);
    }

    .agenda-row {
        grid-template-columns: 54px 28px minmax(0, 1fr);
    }

    .agenda-time {
        padding-top: 17px;
    }

    .agenda-time strong {
        font-size: 0.75rem;
    }

    .agenda-rail span {
        width: 20px;
        height: 20px;
        margin-top: 18px;
        font-size: 0.48rem;
    }

    .agenda-card {
        align-items: flex-start;
        flex-direction: column;
        gap: 13px;
        padding: 14px;
    }

    .agenda-card__actions {
        width: 100%;
        justify-content: space-between;
    }

    .week-calendar {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 430px) {
    .calendar-overview > div {
        padding: 12px;
    }

    .agenda-card__title {
        align-items: flex-start;
        flex-direction: column;
        gap: 6px;
    }

    .agenda-card__actions {
        align-items: flex-start;
        flex-direction: column;
    }

    .calendar-dropzone {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
