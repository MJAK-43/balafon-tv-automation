import { defineStore } from 'pinia';
import { api } from '../services/api';

export const useBroadcastStore = defineStore('broadcast', {
    state: () => ({
        channels: [],
        mediaAssets: [],
        brandingAssets: [],
        mediaPagination: {
            current_page: 1,
            last_page: 1,
            per_page: 18,
            total: 0,
        },
        playlists: [],
        schedules: [],
        conflicts: [],
        controlCenter: null,
    }),
    actions: {
        async fetchChannels() {
            const { data } = await api.get('/channels');
            this.channels = data.data ?? data;

            return this.channels;
        },
        async createChannel(payload) {
            const { data } = await api.post('/channels', payload);
            await this.fetchChannels();

            return data;
        },
        async updateChannel(uuid, payload) {
            const { data } = await api.put(`/channels/${uuid}`, payload);
            await this.fetchChannels();

            return data;
        },
        async deleteChannel(uuid) {
            await api.delete(`/channels/${uuid}`);
            await this.fetchChannels();
        },
        async fetchMediaAssets(filters = {}) {
            const { data } = await api.get('/media-assets', {
                params: { ...filters },
            });

            if (Array.isArray(data?.data)) {
                this.mediaAssets = data.data;
                this.mediaPagination = {
                    current_page: data.current_page ?? 1,
                    last_page: data.last_page ?? 1,
                    per_page: data.per_page ?? filters.per_page ?? 18,
                    total: data.total ?? data.data.length,
                };

                return data;
            }

            this.mediaAssets = data.data ?? data;
            this.mediaPagination = {
                current_page: 1,
                last_page: 1,
                per_page: Array.isArray(this.mediaAssets) ? this.mediaAssets.length : 18,
                total: Array.isArray(this.mediaAssets) ? this.mediaAssets.length : 0,
            };

            return this.mediaAssets;
        },
        async fetchPlaylists(filters = {}) {
            const { data } = await api.get('/playlists', {
                params: { ...filters, per_page: 100 },
            });
            this.playlists = data.data ?? data;

            return this.playlists;
        },
        async fetchBrandingAssets(filters = {}) {
            const { data } = await api.get('/branding-assets', { params: filters });
            this.brandingAssets = data;

            return this.brandingAssets;
        },
        async uploadBrandingLogo(file, payload) {
            const formData = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    formData.append(key, value);
                }
            });
            formData.append('file', file);
            const { data } = await api.post('/branding-assets/logos', formData);
            await this.fetchBrandingAssets();

            return data;
        },
        async createBrandingAnnouncement(payload, file = null) {
            const formData = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
                }
            });
            if (file) {
                formData.append('file', file);
            }
            const { data } = await api.post('/branding-assets/announcements', formData);
            await this.fetchBrandingAssets();

            return data;
        },
        async updateBrandingAsset(uuid, payload) {
            const { data } = await api.put(`/branding-assets/${uuid}`, payload);
            await this.fetchBrandingAssets();

            return data;
        },
        async deleteBrandingAsset(uuid) {
            await api.delete(`/branding-assets/${uuid}`);
            await this.fetchBrandingAssets();
        },
        fetchBrandingPreview(uuid) {
            return api.get(`/branding-assets/${uuid}/preview`, {
                responseType: 'blob',
            });
        },
        async fetchSchedules(filters = {}) {
            const { data } = await api.get('/schedules', {
                params: { mode: 'all', ...filters },
            });
            this.schedules = data.data ?? data;

            return this.schedules;
        },
        async fetchConflicts(filters = {}) {
            const { data } = await api.get('/schedule-conflicts', { params: filters });
            this.conflicts = data;

            return this.conflicts;
        },
        browseMedia(path = null) {
            return api.get('/media-assets/browser', {
                params: path ? { path } : {},
            });
        },
        fetchMediaRoot() {
            return api.get('/system/media-root');
        },
        updateMediaRoot(payload) {
            return api.put('/system/media-root', payload);
        },
        previewMediaFolderImport(payload) {
            return api.post('/media-assets/import-folder/preview', payload);
        },
        async importMediaFolder(payload) {
            const { data } = await api.post('/media-assets/import-folder', payload);
            await this.fetchMediaAssets();

            return data;
        },
        async uploadSingleMedia(file, metadata = {}, options = {}) {
            const formData = new FormData();
            formData.append('file', file);
            Object.entries(metadata).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    formData.append(key, value);
                }
            });
            const { data } = await api.post('/media-assets/upload-single', formData, {
                onUploadProgress: options.onUploadProgress,
            });
            await this.fetchMediaAssets();

            return data;
        },
        async uploadMediaFolder(files, options = {}) {
            const formData = new FormData();
            files.forEach((file, index) => {
                formData.append('files[]', file, file.name);
                formData.append('durations[]', options.durations?.[index] ?? '');
            });
            const { data } = await api.post('/media-assets/upload-folder', formData, {
                onUploadProgress: options.onUploadProgress,
            });
            if (options.refresh !== false) {
                await this.fetchMediaAssets();
            }

            return data;
        },
        async createMediaAsset(payload) {
            const { data } = await api.post('/media-assets', payload);
            await this.fetchMediaAssets();

            return data;
        },
        async updateMediaAsset(uuid, payload) {
            const { data } = await api.put(`/media-assets/${uuid}`, payload);
            await this.fetchMediaAssets();

            return data;
        },
        async deleteMediaAsset(uuid) {
            await api.delete(`/media-assets/${uuid}`);
            await this.fetchMediaAssets();
        },
        async createPlaylist(payload) {
            const { data } = await api.post('/playlists', payload);
            await this.fetchPlaylists();

            return data;
        },
        async updatePlaylist(uuid, payload) {
            const { data } = await api.put(`/playlists/${uuid}`, payload);
            await this.fetchPlaylists();

            return data;
        },
        async deletePlaylist(uuid) {
            await api.delete(`/playlists/${uuid}`);
            await this.fetchPlaylists();
        },
        async createSchedule(payload) {
            const { data } = await api.post('/schedules', payload);

            return data;
        },
        async updateSchedule(uuid, payload) {
            const { data } = await api.put(`/schedules/${uuid}`, payload);

            return data;
        },
        async deleteSchedule(uuid) {
            await api.delete(`/schedules/${uuid}`);
        },
        duplicateDay(payload) {
            return api.post('/schedules/duplicate-day', payload);
        },
        duplicateWeek(payload) {
            return api.post('/schedules/duplicate-week', payload);
        },
        fetchMediaPreview(uuid) {
            return api.get(`/media-assets/${uuid}/preview`, {
                responseType: 'blob',
            });
        },
        async fetchControlCenter() {
            const { data } = await api.get('/automation/control-center');
            this.controlCenter = data;

            return data;
        },
        tickAutomation() {
            return api.post('/automation/tick');
        },
    },
});
