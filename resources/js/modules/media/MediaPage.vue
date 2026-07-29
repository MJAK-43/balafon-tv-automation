<template>
    <AppLayout title="Médias" subtitle="Catalogue des contenus diffusés, avec recherche, inspection et prévisualisation.">
        <PageHeader
            eyebrow="Médiathèque"
            title="Tous vos contenus. Bien ordonnés."
            description="Référencez les médias sans duplication et conservez une bibliothèque immédiatement exploitable à l’antenne."
        >
            <button class="rounded-full border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-200 transition hover:bg-white/[0.06]" @click="viewMode = viewMode === 'grid' ? 'list' : 'grid'">
                {{ viewMode === 'grid' ? 'List mode' : 'Card mode' }}
            </button>
            <button class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_12px_30px_rgba(245,158,11,0.24)] transition hover:bg-amber-400" @click="openCreateDialog">
                Add media
            </button>
        </PageHeader>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total assets" :value="pagination.total" hint="catalog size" />
            <StatCard label="Ready" :value="readyCount" hint="broadcast available" />
            <StatCard label="Archived" :value="archivedCount" hint="inactive catalog" />
            <StatCard label="Visible results" :value="filteredMedia.length" hint="current filters" />
        </section>

        <section class="rounded-[1.75rem] border border-white/8 bg-[#0f1728]/78 p-4 shadow-[0_18px_42px_rgba(0,0,0,0.14)]">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <label class="space-y-2 text-sm">
                    <span class="text-slate-300">Media root path</span>
                    <input
                        v-model="mediaRootForm.path"
                        class="w-full rounded-[1.2rem] border border-white/8 bg-[#0b1220] px-4 py-3 text-sm text-white outline-none"
                        placeholder="Example: D:\\BalafonMedia"
                        type="text"
                    >
                </label>
                <button
                    class="rounded-full bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="mediaRootSaving || !mediaRootForm.path.trim()"
                    @click="saveMediaRoot"
                >
                    {{ mediaRootSaving ? 'Saving...' : 'Save media root' }}
                </button>
            </div>
            <p class="mt-3 text-sm text-slate-400">
                Balafon creates and uses a default media root automatically. You can change it here later if needed, and referenced files will stay under this root instead of being duplicated.
            </p>
            <p v-if="mediaRootMessage" class="mt-2 text-sm text-emerald-300">{{ mediaRootMessage }}</p>
            <p v-if="mediaRootError" class="mt-2 text-sm text-rose-300">{{ mediaRootError }}</p>
        </section>

        <section v-if="importSuccess" class="rounded-[1.5rem] border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
            {{ importSuccess }}
        </section>

        <section class="rounded-[1.75rem] border border-white/8 bg-[#0f1728]/78 p-4 shadow-[0_18px_42px_rgba(0,0,0,0.14)]">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1.4fr)_220px_220px_auto]">
                <SearchBar v-model="filters.search" placeholder="Search title, path or description" />
                <select v-model="filters.media_type" class="w-full rounded-[1.2rem] border border-white/8 bg-[#0b1220] px-4 py-3 text-sm text-white outline-none">
                    <option value="">All types</option>
                    <option v-for="type in mediaTypes" :key="type" :value="type">{{ type }}</option>
                </select>
                <select v-model="filters.status" class="w-full rounded-[1.2rem] border border-white/8 bg-[#0b1220] px-4 py-3 text-sm text-white outline-none">
                    <option value="">All statuses</option>
                    <option v-for="status in mediaStatuses" :key="status" :value="status">{{ status }}</option>
                </select>
                <button class="rounded-[1.2rem] border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-slate-300 transition hover:bg-white/[0.06] hover:text-white" @click="resetFilters">
                    Reset filters
                </button>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-4">
                <div v-if="loading">
                    <LoadingState />
                </div>

                <div v-else-if="filteredMedia.length === 0">
                    <EmptyState title="No media referenced yet" description="Use Add media to register a single file or import a complete folder." />
                </div>

                <div v-else-if="viewMode === 'grid'" class="grid gap-4 md:grid-cols-2 2xl:grid-cols-2 3xl:grid-cols-3">
                    <BroadcastCard
                        v-for="item in filteredMedia"
                        :key="item.uuid"
                        eyebrow="Asset"
                        :title="item.title"
                        :description="item.description || item.file_path"
                    >
                        <template #status>
                            <StatusBadge :status="item.status" />
                        </template>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs uppercase tracking-[0.2em] text-slate-500">
                                <span>{{ item.media_type }}</span>
                                <span>{{ formatDuration(item.duration_seconds) }}</span>
                            </div>
                            <div class="truncate rounded-2xl border border-white/10 bg-black/20 px-3 py-2 text-xs text-slate-400">
                                {{ item.file_path }}
                            </div>
                            <div class="flex gap-2">
                                <button class="rounded-full border border-white/10 px-3 py-2 text-xs text-slate-200" @click="selectMedia(item)">Preview</button>
                                <button class="rounded-full border border-white/10 px-3 py-2 text-xs text-slate-200" @click="openEditDialog(item)">Edit</button>
                                <button class="rounded-full border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs text-rose-200" @click="confirmDelete(item)">Delete</button>
                            </div>
                        </div>
                    </BroadcastCard>
                </div>

                <div v-else class="overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-950/45">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/[0.04] text-left text-xs uppercase tracking-[0.25em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Duration</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Path</th>
                                <th class="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="item in filteredMedia" :key="item.uuid" class="hover:bg-white/[0.03]">
                                <td class="px-4 py-4 font-medium text-white">{{ item.title }}</td>
                                <td class="px-4 py-4 text-slate-300">{{ item.media_type }}</td>
                                <td class="px-4 py-4 text-slate-300">{{ formatDuration(item.duration_seconds) }}</td>
                                <td class="px-4 py-4"><StatusBadge :status="item.status" /></td>
                                <td class="max-w-xs truncate px-4 py-4 text-slate-400">{{ item.file_path }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button class="rounded-full border border-white/10 px-3 py-2 text-xs text-slate-200" @click="selectMedia(item)">Preview</button>
                                        <button class="rounded-full border border-white/10 px-3 py-2 text-xs text-slate-200" @click="openEditDialog(item)">Edit</button>
                                        <button class="rounded-full border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs text-rose-200" @click="confirmDelete(item)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pagination.last_page > 1" class="flex items-center justify-between rounded-[1.5rem] border border-white/8 bg-[#0f1728]/72 px-4 py-3">
                    <p class="text-sm text-slate-400">
                        Page {{ pagination.current_page }} / {{ pagination.last_page }} - {{ pagination.total }} medias
                    </p>
                    <div class="flex gap-2">
                        <button
                            class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="pagination.current_page <= 1 || loading"
                            @click="changePage(pagination.current_page - 1)"
                        >
                            Previous
                        </button>
                        <button
                            class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="pagination.current_page >= pagination.last_page || loading"
                            @click="changePage(pagination.current_page + 1)"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <TimelineCard eyebrow="Preview" title="Selected asset">
                <div v-if="selectedMedia" class="space-y-4">
                    <div class="rounded-[1.5rem] border border-white/10 bg-black/20 p-3">
                        <video v-if="previewUrl && isVideoPreview" :src="previewUrl" class="max-h-64 w-full rounded-2xl bg-black" controls />
                        <audio v-else-if="previewUrl" :src="previewUrl" class="w-full" controls />
                        <EmptyState v-else title="Preview not loaded" description="Select an asset to stream a local preview through the API." />
                    </div>

                    <div class="space-y-3 rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Title</p>
                            <p class="mt-1 text-sm text-white">{{ selectedMedia.title }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Path</p>
                            <p class="mt-1 break-all text-sm text-slate-300">{{ selectedMedia.file_path }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Type</p>
                                <p class="mt-1 text-sm text-white">{{ selectedMedia.media_type }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Duration</p>
                                <p class="mt-1 text-sm text-white">{{ formatDuration(selectedMedia.duration_seconds) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <EmptyState v-else title="No asset selected" description="Select a media card to inspect its metadata and launch a quick preview." />
            </TimelineCard>
        </section>

        <Dialog v-model:visible="dialogVisible" modal :style="{ width: '64rem' }">
            <template #header>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ editingMediaUuid ? 'Edit media asset' : 'Add media asset' }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ editingMediaUuid ? 'Update an existing media reference.' : 'Choose a single file or import a full folder in batch.' }}
                    </p>
                </div>
            </template>

            <div class="space-y-5">
                <div v-if="!editingMediaUuid" class="grid gap-4 md:grid-cols-2">
                    <button
                        class="rounded-[1.5rem] border p-5 text-left transition"
                        :class="addMode === 'file'
                            ? 'border-orange-400/70 bg-orange-500/10 shadow-[inset_3px_0_0_rgba(255,106,77,0.95)]'
                            : 'border-white/10 bg-white/[0.025] hover:border-white/20 hover:bg-white/[0.045]'"
                        @click="addMode = 'file'"
                    >
                        <p class="text-sm font-semibold text-white">Single file</p>
                        <p class="mt-2 text-sm text-slate-400">Add one media quickly with a short form.</p>
                    </button>
                    <button
                        class="rounded-[1.5rem] border p-5 text-left transition"
                        :class="addMode === 'folder'
                            ? 'border-orange-400/70 bg-orange-500/10 shadow-[inset_3px_0_0_rgba(255,106,77,0.95)]'
                            : 'border-white/10 bg-white/[0.025] hover:border-white/20 hover:bg-white/[0.045]'"
                        @click="addMode = 'folder'"
                    >
                        <p class="text-sm font-semibold text-white">Full folder</p>
                        <p class="mt-2 text-sm text-slate-400">Scan a directory and import all supported media in one action.</p>
                    </button>
                </div>

                <p v-if="currentDialogMode === 'file' && importError" class="rounded-[1.25rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ importError }}
                </p>

                <div v-if="currentDialogMode === 'file'" class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="space-y-4">
                        <div v-if="!editingMediaUuid" class="space-y-3">
                            <input
                                ref="singleFileInput"
                                class="hidden"
                                type="file"
                                :accept="supportedMediaAccept"
                                @change="handleSingleFileSelection"
                            >
                            <button
                                class="flex w-full items-center justify-between rounded-[1.5rem] border border-orange-400/40 bg-orange-500/10 px-5 py-4 text-left transition hover:border-orange-400/70 hover:bg-orange-500/15"
                                type="button"
                                @click="openNativeFilePicker"
                            >
                                <span>
                                    <span class="block text-sm font-semibold text-white">Choose a file from this PC</span>
                                    <span class="mt-1 block text-xs text-slate-400">Video, audio or image file</span>
                                </span>
                                <span class="rounded-full bg-amber-500 px-4 py-2 text-xs font-semibold text-slate-950">Choose file</span>
                            </button>

                            <div v-if="selectedUploadFile" class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <p class="truncate text-sm font-medium text-emerald-950">{{ selectedUploadFile.name }}</p>
                                <p class="mt-1 text-xs text-emerald-700">
                                    {{ formatBytes(selectedUploadFile.size) }}
                                    <span v-if="durationDetectionInProgress"> - reading duration...</span>
                                    <span v-else-if="form.duration_seconds !== null"> - {{ formatDuration(form.duration_seconds) }}</span>
                                    <span v-else> - ready to upload</span>
                                </p>
                            </div>

                            <button class="text-sm text-slate-500 underline-offset-4 hover:text-slate-800 hover:underline" type="button" @click="openBrowser('file')">
                                Or choose an existing file from the configured media root
                            </button>
                        </div>

                        <label v-else class="space-y-2 text-sm">
                            <span class="text-slate-600">File path</span>
                            <div class="flex gap-3">
                                <input v-model="form.file_path" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" type="text" placeholder="Choose a file from configured media roots" readonly>
                                <button class="rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700" type="button" @click="openBrowser('file')">Browse</button>
                            </div>
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-600">Title</span>
                                <input v-model="form.title" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" type="text">
                            </label>
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-600">Type</span>
                                <select v-model="form.media_type" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none">
                                    <option v-for="type in mediaTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                            </label>
                        </div>

                        <button
                            v-if="!editingMediaUuid"
                            class="text-sm text-slate-500 transition hover:text-slate-700"
                            @click="advancedVisible = !advancedVisible"
                        >
                            {{ advancedVisible ? 'Hide advanced options' : 'Show advanced options' }}
                        </button>

                        <div v-if="editingMediaUuid || advancedVisible" class="space-y-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-600">Description</span>
                                <textarea v-model="form.description" class="min-h-24 w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" />
                            </label>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-2 text-sm">
                                    <span class="text-slate-600">Duration seconds</span>
                                    <input v-model.number="form.duration_seconds" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" min="0" type="number">
                                </label>
                                <label class="space-y-2 text-sm">
                                    <span class="text-slate-600">Status</span>
                                    <select v-model="form.status" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none">
                                        <option v-for="status in mediaStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Detected info</p>
                            <div class="mt-3 space-y-3 rounded-[1.25rem] border border-slate-200 bg-white p-4 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Title</span>
                                    <span class="font-medium text-slate-900">{{ form.title || 'Auto from filename' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Type</span>
                                    <span class="font-medium text-slate-900">{{ form.media_type }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Duration</span>
                                    <span class="font-medium text-slate-900">
                                        {{ durationDetectionInProgress ? 'Reading...' : formatDuration(form.duration_seconds) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Status</span>
                                    <span class="font-medium text-slate-900">{{ form.status }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.25rem] border border-slate-200 bg-white p-4 text-sm text-slate-600">
                            <span v-if="selectedUploadFile">The selected file will be copied into the configured Balafon media root.</span>
                            <span v-else>Files selected from the media root are referenced without being copied.</span>
                        </div>
                    </div>
                </div>

                <div v-else class="grid gap-5 lg:grid-cols-[1fr_1fr]">
                    <div class="space-y-4">
                        <input
                            ref="folderInput"
                            class="hidden"
                            type="file"
                            :accept="supportedMediaAccept"
                            webkitdirectory
                            directory
                            multiple
                            @change="handleFolderSelection"
                        >
                        <button
                            class="flex w-full items-center justify-between rounded-[1.5rem] border border-orange-400/40 bg-orange-500/10 px-5 py-4 text-left transition hover:border-orange-400/70 hover:bg-orange-500/15"
                            type="button"
                            @click="openNativeFolderPicker"
                        >
                            <span>
                                <span class="block text-sm font-semibold text-white">Choose a folder from this PC</span>
                                <span class="mt-1 block text-xs text-slate-400">Supported media in subfolders are included</span>
                            </span>
                            <span class="rounded-full bg-amber-500 px-4 py-2 text-xs font-semibold text-slate-950">Choose folder</span>
                        </button>

                        <div v-if="selectedFolderFiles.length > 0" class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <p class="truncate text-sm font-medium text-emerald-950">{{ selectedFolderName }}</p>
                            <p class="mt-1 text-xs text-emerald-700">
                                {{ selectedFolderFiles.length }} supported file(s), {{ formatBytes(selectedFolderSize) }}
                            </p>
                            <p v-if="durationDetectionInProgress" class="mt-1 text-xs text-emerald-700">
                                Reading media durations...
                            </p>
                            <p v-if="ignoredFolderFiles > 0" class="mt-1 text-xs text-amber-700">
                                {{ ignoredFolderFiles }} unsupported file(s) ignored
                            </p>
                        </div>

                        <label class="space-y-2 text-sm">
                            <span class="text-slate-600">Or reference an existing folder from the media root</span>
                            <div class="flex gap-3">
                                <input v-model="folderImport.path" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none" type="text" placeholder="Choose a folder from configured media roots" readonly>
                                <button class="rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700" type="button" @click="openBrowser('folder')">Browse</button>
                            </div>
                        </label>

                        <label v-if="selectedFolderFiles.length === 0" class="flex items-center gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input v-model="folderImport.recursive" type="checkbox">
                            <span>Include subfolders</span>
                        </label>

                        <label v-if="selectedFolderFiles.length === 0" class="flex items-center gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input v-model="folderImport.ignoreDuplicates" type="checkbox">
                            <span>Ignore already imported files</span>
                        </label>

                        <button v-if="selectedFolderFiles.length === 0" class="cursor-pointer rounded-full bg-slate-900 px-5 py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50" :disabled="!folderImport.path || importInProgress" type="button" @click="previewNativeFolderImport">
                            Preview folder import
                        </button>
                        <p class="text-sm text-slate-600">
                            <span v-if="selectedFolderFiles.length > 0">Files selected from this PC will be copied into the Balafon media root.</span>
                            <span v-else>Folders selected from the media root are referenced without copying files.</span>
                        </p>
                        <p v-if="folderPreview.length > 0" class="text-xs text-slate-500">
                            {{ folderPreview.length }} fichier(s) detecte(s) dans le dossier selectionne.
                        </p>
                    </div>

                    <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Folder preview</p>
                                <p class="mt-1 text-sm text-slate-600">{{ folderPreview.length }} supported files found</p>
                            </div>
                            <button
                                class="cursor-pointer rounded-full bg-amber-500 px-4 py-2 text-sm font-medium text-slate-950 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="folderPreview.length === 0 || importInProgress || durationDetectionInProgress"
                                type="button"
                                @click="importFolder"
                            >
                                {{ durationDetectionInProgress ? 'Reading durations...' : (importInProgress ? `Importing... ${uploadProgress}%` : 'Import folder') }}
                            </button>
                        </div>

                        <p v-if="importError" class="text-sm text-rose-600">
                            {{ importError }}
                        </p>

                        <div v-if="importInProgress" class="space-y-2 rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <div class="flex items-center justify-between gap-3">
                                <span>Import in progress...</span>
                                <span>{{ uploadProgress }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-amber-100">
                                <div class="h-full rounded-full bg-amber-500 transition-[width]" :style="{ width: `${uploadProgress}%` }" />
                            </div>
                        </div>

                        <div v-if="folderPreview.length === 0" class="rounded-[1.25rem] border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
                            Choose a directory, then preview the indexed files before creating media references.
                        </div>

                        <div v-else class="max-h-[28rem] space-y-2 overflow-auto">
                            <div
                                v-for="file in folderPreview"
                                :key="file.file_path"
                                class="rounded-[1.1rem] border px-4 py-3"
                                :class="file.is_duplicate ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ file.title }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ file.file_path || file.file_name }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-medium"
                                        :class="file.is_duplicate ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                    >
                                        {{ file.is_duplicate ? 'Duplicate' : 'New' }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center gap-3 text-[11px] uppercase tracking-[0.18em] text-slate-500">
                                    <span>{{ file.media_type }}</span>
                                    <span>{{ file.extension }}</span>
                                    <span v-if="file.duration_seconds !== null && file.duration_seconds !== undefined">{{ formatDuration(file.duration_seconds) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="browserVisible" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Browser</p>
                                <p class="mt-1 text-sm text-slate-600">{{ selectorMode === 'folder' ? 'Choose a folder to import' : 'Choose a media file' }}</p>
                            </div>
                        <div class="flex items-center gap-3">
                            <button v-if="browserState.parent_path" class="text-sm text-slate-500" @click="browse(browserState.parent_path)">Up</button>
                            <button class="text-sm text-slate-500" @click="browserVisible = false">Close</button>
                        </div>
                    </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
                            <div class="space-y-2">
                                <button
                                    v-for="root in browserState.roots"
                                    :key="`root-${root.path}`"
                                    class="flex w-full items-center justify-between rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-left text-sm text-amber-900"
                                    @click="browse(root.path)"
                                >
                                    <span class="truncate">{{ root.name }}</span>
                                    <span class="text-xs text-amber-700">Root</span>
                                </button>
                                <button
                                    v-for="directory in browserState.directories"
                                    :key="directory.path"
                                    class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-700"
                                    @click="browse(directory.path)"
                            >
                                <span>{{ directory.name }}</span>
                                <span class="text-xs text-slate-400">Open</span>
                            </button>
                        </div>

                        <div class="space-y-2">
                            <button
                                v-if="selectorMode === 'folder' && browserState.current_path"
                                class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-3 py-3 text-left text-sm font-medium text-amber-900"
                                @click="selectBrowserFolder"
                            >
                                Use current folder
                            </button>

                            <button
                                v-for="file in browserState.files"
                                :key="file.path"
                                class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-700"
                                :disabled="selectorMode === 'folder'"
                                @click="selectBrowserFile(file)"
                            >
                                <span class="truncate">{{ file.name }}</span>
                                <span class="text-xs uppercase text-slate-400">{{ file.extension }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600" @click="dialogVisible = false">Cancel</button>
                    <button
                        v-if="currentDialogMode === 'file'"
                        class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="importInProgress || durationDetectionInProgress || (!editingMediaUuid && !selectedUploadFile && !form.file_path)"
                        @click="saveMedia"
                    >
                        {{ durationDetectionInProgress ? 'Reading duration...' : (importInProgress ? `Uploading... ${uploadProgress}%` : (editingMediaUuid ? 'Save changes' : 'Add media')) }}
                    </button>
                </div>
            </template>
        </Dialog>

        <ConfirmDialog
            v-model:visible="deleteVisible"
            title="Delete media asset"
            description="The file on disk will remain untouched. Only the Balafon reference will be removed."
            @confirm="deleteMedia"
        />
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import AppLayout from '../../layouts/AppLayout.vue';
import { useBroadcastStore } from '../../stores/broadcast';
import BroadcastCard from '../../ui/BroadcastCard.vue';
import ConfirmDialog from '../../ui/ConfirmDialog.vue';
import EmptyState from '../../ui/EmptyState.vue';
import LoadingState from '../../ui/LoadingState.vue';
import PageHeader from '../../ui/PageHeader.vue';
import SearchBar from '../../ui/SearchBar.vue';
import StatCard from '../../ui/StatCard.vue';
import StatusBadge from '../../ui/StatusBadge.vue';
import TimelineCard from '../../ui/TimelineCard.vue';

const broadcast = useBroadcastStore();
const loading = ref(true);
const viewMode = ref('grid');
const dialogVisible = ref(false);
const browserVisible = ref(false);
const singleFileInput = ref(null);
const folderInput = ref(null);
const selectedUploadFile = ref(null);
const selectedFolderFiles = ref([]);
const selectedFolderDurations = ref(new Map());
const ignoredFolderFiles = ref(0);
const selectorMode = ref('file');
const addMode = ref('file');
const advancedVisible = ref(false);
const deleteVisible = ref(false);
const editingMediaUuid = ref(null);
const mediaToDelete = ref(null);
const selectedMedia = ref(null);
const previewUrl = ref('');
const folderPreview = ref([]);
const importError = ref('');
const importInProgress = ref(false);
const importSuccess = ref('');
const uploadProgress = ref(0);
const durationDetectionInProgress = ref(false);
const durationDetectionSequence = ref(0);
const currentPage = ref(1);
const filterDebounce = ref(null);
const mediaRootSaving = ref(false);
const mediaRootMessage = ref('');
const mediaRootError = ref('');

const mediaTypes = ['PROGRAM', 'MOVIE', 'ADVERTISEMENT', 'JINGLE', 'LIVE_PLACEHOLDER'];
const mediaStatuses = ['READY', 'ARCHIVED'];
const supportedMediaExtensions = ['mp4', 'mov', 'mxf', 'avi', 'mkv', 'mp3', 'wav', 'aac', 'm4a', 'jpg', 'jpeg', 'png'];
const timedMediaExtensions = ['mp4', 'mov', 'mxf', 'avi', 'mkv', 'mp3', 'wav', 'aac', 'm4a'];
const supportedMediaAccept = supportedMediaExtensions.map((extension) => `.${extension}`).join(',');
const pageSize = 18;

const filters = reactive({
    search: '',
    media_type: '',
    status: '',
});

const form = reactive({
    title: '',
    description: '',
    media_type: 'PROGRAM',
    file_path: '',
    duration_seconds: null,
    status: 'READY',
});

const folderImport = reactive({
    path: '',
    recursive: true,
    ignoreDuplicates: true,
});

const browserState = reactive({
    roots: [],
    directories: [],
    files: [],
    parent_path: null,
    current_path: null,
});

const mediaRootForm = reactive({
    path: '',
});

const mediaAssets = computed(() => broadcast.mediaAssets);
const pagination = computed(() => broadcast.mediaPagination);
const currentDialogMode = computed(() => editingMediaUuid.value ? 'file' : addMode.value);
const filteredMedia = computed(() => mediaAssets.value);
const selectedFolderName = computed(() => {
    const relativePath = selectedFolderFiles.value[0]?.webkitRelativePath ?? '';

    return relativePath.split('/')[0] || 'Selected folder';
});
const selectedFolderSize = computed(() => selectedFolderFiles.value.reduce((total, file) => total + file.size, 0));

const readyCount = computed(() => mediaAssets.value.filter((item) => item.status === 'READY').length);
const archivedCount = computed(() => mediaAssets.value.filter((item) => item.status === 'ARCHIVED').length);
const isVideoPreview = computed(() => selectedMedia.value && ['PROGRAM', 'MOVIE', 'ADVERTISEMENT', 'JINGLE'].includes(selectedMedia.value.media_type));

const loadMedia = async () => {
    loading.value = true;
    try {
        await broadcast.fetchMediaAssets({
            search: filters.search || undefined,
            media_type: filters.media_type || undefined,
            status: filters.status || undefined,
            page: currentPage.value,
            per_page: pageSize,
        });
    } finally {
        loading.value = false;
    }
};

const loadMediaRoot = async () => {
    try {
        const response = await broadcast.fetchMediaRoot();
        mediaRootForm.path = response.data?.media_root ?? '';
    } catch {
        mediaRootForm.path = '';
    }
};

const browse = async (path = null) => {
    const response = await broadcast.browseMedia(path);
    Object.assign(browserState, response.data);
};

const openBrowser = (mode) => {
    selectorMode.value = mode;
    browserVisible.value = true;
    browse();
};

const saveMediaRoot = async () => {
    mediaRootMessage.value = '';
    mediaRootError.value = '';
    mediaRootSaving.value = true;

    try {
        const response = await broadcast.updateMediaRoot({
            media_root: mediaRootForm.path.trim(),
        });
        mediaRootForm.path = response.data?.media_root ?? mediaRootForm.path.trim();
        mediaRootMessage.value = 'Media root saved.';
        if (browserVisible.value) {
            await browse();
        }
    } catch (error) {
        mediaRootError.value = error?.response?.data?.message || 'Media root could not be saved.';
    } finally {
        mediaRootSaving.value = false;
    }
};

const guessMediaTypeFromPath = (filePath) => {
    const extension = filePath.split('.').pop()?.toLowerCase() ?? '';

    if (['mp3', 'wav', 'aac', 'm4a'].includes(extension)) {
        return 'JINGLE';
    }

    if (['jpg', 'jpeg', 'png'].includes(extension)) {
        return 'LIVE_PLACEHOLDER';
    }

    return 'PROGRAM';
};

const isSupportedMediaFile = (file) => {
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    return supportedMediaExtensions.includes(extension);
};

const mediaFileKey = (file) => file.webkitRelativePath || `${file.name}:${file.size}:${file.lastModified}`;

const detectMediaDuration = (file) => {
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    if (!timedMediaExtensions.includes(extension)) {
        return Promise.resolve(null);
    }

    return new Promise((resolve) => {
        const media = document.createElement(['mp3', 'wav', 'aac', 'm4a'].includes(extension) ? 'audio' : 'video');
        const objectUrl = URL.createObjectURL(file);
        let settled = false;

        const finish = (duration = null) => {
            if (settled) {
                return;
            }

            settled = true;
            window.clearTimeout(timeoutId);
            media.removeAttribute('src');
            media.load();
            URL.revokeObjectURL(objectUrl);
            resolve(duration);
        };

        const timeoutId = window.setTimeout(() => finish(), 15000);

        media.preload = 'metadata';
        media.onloadedmetadata = () => {
            const duration = Number.isFinite(media.duration) && media.duration > 0
                ? Math.ceil(media.duration)
                : null;
            finish(duration);
        };
        media.onerror = () => finish();
        media.src = objectUrl;
    });
};

const detectFolderDurations = async (files, sequence) => {
    const durations = new Map();
    let nextIndex = 0;
    const workerCount = Math.min(4, files.length);

    const worker = async () => {
        while (nextIndex < files.length) {
            const index = nextIndex;
            nextIndex += 1;
            const file = files[index];
            durations.set(mediaFileKey(file), await detectMediaDuration(file));
        }
    };

    await Promise.all(Array.from({ length: workerCount }, () => worker()));

    if (sequence !== durationDetectionSequence.value) {
        return;
    }

    selectedFolderDurations.value = durations;
    folderPreview.value = folderPreview.value.map((file, index) => ({
        ...file,
        duration_seconds: durations.get(mediaFileKey(files[index])) ?? null,
    }));
};

const openNativeFilePicker = () => {
    if (!singleFileInput.value) {
        return;
    }

    singleFileInput.value.value = '';
    singleFileInput.value.click();
};

const handleSingleFileSelection = async (event) => {
    const file = event.target.files?.[0] ?? null;

    if (!file) {
        return;
    }

    const sequence = ++durationDetectionSequence.value;

    if (!isSupportedMediaFile(file)) {
        selectedUploadFile.value = null;
        durationDetectionInProgress.value = false;
        importError.value = 'This file type is not supported.';
        return;
    }

    importError.value = '';
    selectedUploadFile.value = file;
    form.file_path = file.name;
    form.title = file.name.replace(/\.[^/.]+$/, '');
    form.media_type = guessMediaTypeFromPath(file.name);
    form.duration_seconds = null;
    browserVisible.value = false;

    durationDetectionInProgress.value = true;

    try {
        const duration = await detectMediaDuration(file);

        if (sequence === durationDetectionSequence.value) {
            form.duration_seconds = duration;
        }
    } finally {
        if (sequence === durationDetectionSequence.value) {
            durationDetectionInProgress.value = false;
        }
    }
};

const openNativeFolderPicker = () => {
    if (!folderInput.value) {
        return;
    }

    folderInput.value.value = '';
    folderInput.value.click();
};

const handleFolderSelection = async (event) => {
    const files = Array.from(event.target.files ?? []);
    const supportedFiles = files.filter(isSupportedMediaFile);
    const sequence = ++durationDetectionSequence.value;

    selectedFolderFiles.value = supportedFiles;
    selectedFolderDurations.value = new Map();
    ignoredFolderFiles.value = files.length - supportedFiles.length;
    folderImport.path = '';
    browserVisible.value = false;
    uploadProgress.value = 0;
    importError.value = supportedFiles.length === 0 && files.length > 0
        ? 'No supported media files were found in this folder.'
        : '';

    folderPreview.value = supportedFiles.map((file) => {
        const relativePath = file.webkitRelativePath || file.name;
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        return {
            title: file.name.replace(/\.[^/.]+$/, ''),
            file_name: file.name,
            file_path: relativePath,
            extension,
            media_type: guessMediaTypeFromPath(file.name),
            is_duplicate: false,
            size_bytes: file.size,
            duration_seconds: null,
        };
    });

    durationDetectionInProgress.value = supportedFiles.length > 0;

    try {
        await detectFolderDurations(supportedFiles, sequence);
    } finally {
        if (sequence === durationDetectionSequence.value) {
            durationDetectionInProgress.value = false;
        }
    }
};

const selectBrowserFile = (file) => {
    if (selectorMode.value !== 'file') {
        return;
    }

    selectedUploadFile.value = null;
    durationDetectionSequence.value += 1;
    durationDetectionInProgress.value = false;
    form.duration_seconds = null;
    form.file_path = file.path;
    if (!form.title) {
        form.title = file.name.replace(/\.[^/.]+$/, '');
    }
    form.media_type = guessMediaTypeFromPath(file.path);
    browserVisible.value = false;
};

const selectBrowserFolder = () => {
    if (!browserState.current_path) {
        return;
    }

    selectedFolderFiles.value = [];
    selectedFolderDurations.value = new Map();
    durationDetectionSequence.value += 1;
    durationDetectionInProgress.value = false;
    ignoredFolderFiles.value = 0;
    folderPreview.value = [];
    folderImport.path = browserState.current_path;
    browserVisible.value = false;
};

const resetForm = () => {
    editingMediaUuid.value = null;
    addMode.value = 'file';
    advancedVisible.value = false;
    browserVisible.value = false;
    selectedUploadFile.value = null;
    selectedFolderFiles.value = [];
    selectedFolderDurations.value = new Map();
    durationDetectionSequence.value += 1;
    durationDetectionInProgress.value = false;
    ignoredFolderFiles.value = 0;
    folderPreview.value = [];
    importError.value = '';
    importInProgress.value = false;
    importSuccess.value = '';
    uploadProgress.value = 0;
    form.title = '';
    form.description = '';
    form.media_type = 'PROGRAM';
    form.file_path = '';
    form.duration_seconds = null;
    form.status = 'READY';
    folderImport.path = '';
    folderImport.recursive = true;
    folderImport.ignoreDuplicates = true;
};

const openCreateDialog = () => {
    resetForm();
    dialogVisible.value = true;
};

const openEditDialog = (item) => {
    resetForm();
    editingMediaUuid.value = item.uuid;
    form.title = item.title;
    form.description = item.description;
    form.media_type = item.media_type;
    form.file_path = item.file_path;
    form.duration_seconds = item.duration_seconds;
    form.status = item.status;
    advancedVisible.value = true;
    dialogVisible.value = true;
};

const saveMedia = async () => {
    importError.value = '';
    uploadProgress.value = 0;

    const payload = {
        title: form.title,
        description: form.description || null,
        media_type: form.media_type,
        file_path: form.file_path,
        duration_seconds: form.duration_seconds === null || form.duration_seconds === '' ? null : Number(form.duration_seconds),
        status: form.status || 'READY',
    };

    importInProgress.value = true;

    try {
        if (editingMediaUuid.value) {
            await broadcast.updateMediaAsset(editingMediaUuid.value, payload);
        } else if (selectedUploadFile.value) {
            const result = await broadcast.uploadSingleMedia(selectedUploadFile.value, payload, {
                onUploadProgress: (event) => {
                    if (event.total) {
                        uploadProgress.value = Math.round((event.loaded / event.total) * 100);
                    }
                },
            });
            importSuccess.value = `${result?.imported_count ?? 0} file uploaded successfully.`;
        } else {
            await broadcast.createMediaAsset({
                ...payload,
                status: 'READY',
                duration_seconds: null,
            });
        }
    } catch (error) {
        importError.value = resolveApiError(error, 'Media import failed.');
        return;
    } finally {
        importInProgress.value = false;
    }

    uploadProgress.value = 100;
    dialogVisible.value = false;
    await loadMedia();
};

const previewNativeFolderImport = async () => {
    importError.value = '';
    importSuccess.value = '';

    if (!folderImport.path) {
        return;
    }

    try {
        const response = await broadcast.previewMediaFolderImport({
            path: folderImport.path,
            recursive: folderImport.recursive,
        });
        folderPreview.value = response.data?.files ?? [];
    } catch (error) {
        folderPreview.value = [];
        importError.value = error?.response?.data?.message || 'Folder preview failed.';
    }
};

const importFolder = async () => {
    importError.value = '';
    importSuccess.value = '';

    if (selectedFolderFiles.value.length === 0 && !folderImport.path) {
        return;
    }

    importInProgress.value = true;
    uploadProgress.value = 0;

    try {
        if (selectedFolderFiles.value.length > 0) {
            const files = selectedFolderFiles.value;
            const totalBytes = files.reduce((total, file) => total + file.size, 0);
            const batches = createUploadBatches(files);
            let completedBytes = 0;
            let importedCount = 0;

            for (const batch of batches) {
                const batchBytes = batch.reduce((total, file) => total + file.size, 0);
                const result = await broadcast.uploadMediaFolder(batch, {
                    refresh: false,
                    durations: batch.map((file) => selectedFolderDurations.value.get(mediaFileKey(file)) ?? null),
                    onUploadProgress: (event) => {
                        const currentBytes = event.total
                            ? Math.min(batchBytes, (event.loaded / event.total) * batchBytes)
                            : 0;
                        uploadProgress.value = totalBytes > 0
                            ? Math.round(((completedBytes + currentBytes) / totalBytes) * 100)
                            : 0;
                    },
                });

                completedBytes += batchBytes;
                importedCount += result?.imported_count ?? 0;
                uploadProgress.value = totalBytes > 0
                    ? Math.round((completedBytes / totalBytes) * 100)
                    : 100;
            }

            await loadMedia();
            importSuccess.value = `${importedCount} file(s) uploaded successfully.`;
        } else {
            uploadProgress.value = 20;
            const result = await broadcast.importMediaFolder({
                path: folderImport.path,
                recursive: folderImport.recursive,
                ignore_duplicates: folderImport.ignoreDuplicates,
            });
            uploadProgress.value = 100;
            importSuccess.value = `${result?.imported_count ?? 0} fichier(s) references avec succes.`;
        }
    } catch (error) {
        importError.value = resolveApiError(error, 'Folder import failed.');
        return;
    } finally {
        importInProgress.value = false;
    }

    dialogVisible.value = false;
    await loadMedia();
};

const createUploadBatches = (files) => {
    const maxFilesPerBatch = 20;
    const maxBytesPerBatch = 512 * 1024 * 1024;
    const batches = [];
    let batch = [];
    let batchBytes = 0;

    files.forEach((file) => {
        if (batch.length > 0 && (batch.length >= maxFilesPerBatch || batchBytes + file.size > maxBytesPerBatch)) {
            batches.push(batch);
            batch = [];
            batchBytes = 0;
        }

        batch.push(file);
        batchBytes += file.size;
    });

    if (batch.length > 0) {
        batches.push(batch);
    }

    return batches;
};

const resolveApiError = (error, fallback) => {
    const status = error?.response?.status;
    const validationErrors = error?.response?.data?.errors;
    const firstValidationError = validationErrors
        ? Object.values(validationErrors).flat().find(Boolean)
        : null;

    if (status === 401) {
        return 'Your session has expired. Sign in again, then retry the import.';
    }

    if (status === 413) {
        return 'The selected upload is larger than the server limit.';
    }

    return firstValidationError
        || error?.response?.data?.message
        || `${fallback} (${status ?? 'network error'})`;
};

const changePage = async (page) => {
    if (page < 1 || page === currentPage.value) {
        return;
    }

    currentPage.value = page;
    await loadMedia();
};

const selectMedia = async (item) => {
    selectedMedia.value = item;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = '';
    }

    try {
        const response = await broadcast.fetchMediaPreview(item.uuid);
        previewUrl.value = URL.createObjectURL(response.data);
    } catch {
        previewUrl.value = '';
    }
};

const confirmDelete = (item) => {
    mediaToDelete.value = item;
    deleteVisible.value = true;
};

const deleteMedia = async () => {
    if (!mediaToDelete.value) {
        return;
    }

    await broadcast.deleteMediaAsset(mediaToDelete.value.uuid);
    if (selectedMedia.value?.uuid === mediaToDelete.value.uuid) {
        selectedMedia.value = null;
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = '';
        }
    }
    deleteVisible.value = false;
    mediaToDelete.value = null;
};

const resetFilters = () => {
    filters.search = '';
    filters.media_type = '';
    filters.status = '';
    currentPage.value = 1;
};

const formatDuration = (seconds) => {
    if (!seconds && seconds !== 0) {
        return 'n/a';
    }

    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return [hrs, mins, secs]
        .map((value) => String(value).padStart(2, '0'))
        .join(':');
};

const formatBytes = (bytes) => {
    const safeBytes = Number(bytes ?? 0);

    if (safeBytes < 1024) {
        return `${safeBytes} B`;
    }

    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = safeBytes / 1024;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value.toFixed(value >= 10 ? 1 : 2)} ${units[unitIndex]}`;
};

watch(() => form.file_path, (value) => {
    if (!value || editingMediaUuid.value) {
        return;
    }

    if (!form.title) {
        const filename = value.split(/[\\/]/).pop() ?? '';
        form.title = filename.replace(/\.[^/.]+$/, '');
    }

    form.media_type = guessMediaTypeFromPath(value);
});

watch(
    () => [filters.search, filters.media_type, filters.status],
    () => {
        currentPage.value = 1;

        if (filterDebounce.value) {
            clearTimeout(filterDebounce.value);
        }

        filterDebounce.value = setTimeout(() => {
            loadMedia();
        }, 250);
    },
);

onMounted(async () => {
    await Promise.all([
        loadMedia(),
        loadMediaRoot(),
    ]);
});

onBeforeUnmount(() => {
    if (filterDebounce.value) {
        clearTimeout(filterDebounce.value);
    }

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>
