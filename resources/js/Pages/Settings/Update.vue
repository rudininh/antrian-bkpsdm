<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';

defineOptions({
    layout: DashboardLayout,
});

const props = defineProps({
    systemStatus: Object,
    gitStatus: Object,
    commandOutputs: Array,
    updateLog: Object,
    meta: Object,
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const flashWarning = computed(() => page.props.flash?.warning);
const isRunning = computed(() => props.systemStatus?.isRunning ?? false);
const maintenanceMode = computed(() => props.systemStatus?.maintenanceMode ?? false);
const hasLocalChanges = computed(() => props.gitStatus?.hasLocalChanges ?? false);
const gitClean = computed(() => !props.gitStatus?.statusShort);
const canRunUpdate = computed(() => !isRunning.value && props.systemStatus?.updateBatExists && gitClean.value);

let pollTimer = null;

const reloadStatus = () => {
    router.reload({
        preserveScroll: true,
        preserveState: true,
    });
};

const startPolling = () => {
    if (pollTimer || !isRunning.value) {
        return;
    }

    pollTimer = window.setInterval(() => {
        reloadStatus();
    }, 5000);
};

const stopPolling = () => {
    if (!pollTimer) {
        return;
    }

    window.clearInterval(pollTimer);
    pollTimer = null;
};

const runUpdate = () => {
    if (!window.confirm('Jalankan update.bat di server sekarang? Aplikasi bisa masuk maintenance mode sementara.')) {
        return;
    }

    router.post(route('system.update.run'), {}, {
        preserveScroll: true,
    });
};

const runCleanupAction = (mode, confirmation) => {
    if (confirmation && !window.confirm(confirmation)) {
        return;
    }

    router.post(route('system.update.cleanup', mode), {}, {
        preserveScroll: true,
        onSuccess: () => {
            reloadStatus();
        },
    });
};

const runArtisanAction = (action, label, confirmation = null) => {
    if (confirmation && !window.confirm(confirmation)) {
        return;
    }

    router.post(route('system.update.artisan', action), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (label === 'up' || label === 'down' || label === 'optimize-clear') {
                reloadStatus();
            }
        },
    });
};

onMounted(() => {
    startPolling();
});

onBeforeUnmount(() => {
    stopPolling();
});

const pollWatcher = computed(() => isRunning.value);

watch(pollWatcher, (running) => {
    if (running) {
        startPolling();
        return;
    }

    stopPolling();
});
</script>

<template>
    <Head title="Pengaturan Server" />

    <div class="space-y-6">
        <div
            v-if="flashSuccess"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ flashSuccess }}
        </div>

        <div
            v-if="flashError"
            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"
        >
            {{ flashError }}
        </div>

        <div
            v-if="flashWarning"
            class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"
        >
            {{ flashWarning }}
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <article class="min-w-0 rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Status Repository</h3>
                        <p class="mt-1 text-sm text-slate-500">Pantau branch aktif, commit saat ini, dan kondisi sinkronisasi server.</p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="reloadStatus"
                    >
                        Refresh Status
                    </button>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl bg-slate-50 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Branch</p>
                        <p class="mt-3 text-2xl font-semibold text-slate-900">{{ gitStatus.branch }}</p>
                        <p class="mt-2 break-all text-sm text-slate-500">{{ gitStatus.remoteUrl }}</p>
                    </div>

                    <div class="rounded-3xl p-5" :class="gitStatus.isUpToDate ? 'bg-emerald-50' : 'bg-amber-50'">
                        <p class="text-xs uppercase tracking-[0.25em]" :class="gitStatus.isUpToDate ? 'text-emerald-600' : 'text-amber-700'">Sinkronisasi</p>
                        <p class="mt-3 text-2xl font-semibold" :class="gitStatus.isUpToDate ? 'text-emerald-800' : 'text-amber-800'">
                            {{ gitStatus.isUpToDate ? 'Already up to date' : 'Ada update baru' }}
                        </p>
                        <p class="mt-2 text-sm" :class="gitStatus.isUpToDate ? 'text-emerald-700' : 'text-amber-700'">
                            {{ gitStatus.fetch.output }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4">
                    <div class="rounded-3xl border border-slate-100 bg-white p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Commit Lokal</p>
                        <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ gitStatus.localHead || '-' }}</p>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-white p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Commit Remote</p>
                        <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ gitStatus.remoteHead || '-' }}</p>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-white p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Commit Terakhir</p>
                        <p class="mt-2 text-sm text-slate-700">{{ gitStatus.lastCommit || '-' }}</p>
                    </div>
                </div>

                <div class="mt-6 rounded-3xl border border-slate-100 bg-slate-950 p-5 text-slate-100">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">git status</p>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ gitClean ? 'Working tree clean' : 'Ada perubahan lokal yang perlu diperhatikan.' }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="gitClean ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300'"
                        >
                            {{ gitClean ? 'Bersih' : 'Ada Perubahan' }}
                        </span>
                    </div>
                    <pre class="mt-4 overflow-x-auto whitespace-pre-wrap break-words text-sm leading-6 text-slate-200">{{ gitStatus.statusShort || 'Working tree clean' }}</pre>
                </div>
            </article>

            <article class="min-w-0 space-y-6">
                <section class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Kontrol Server</h3>
                            <p class="mt-1 text-sm text-slate-500">Jalankan update dan perintah maintenance langsung dari panel admin.</p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="maintenanceMode ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                        >
                            {{ maintenanceMode ? 'Maintenance Aktif' : 'Online' }}
                        </span>
                    </div>

                    <div class="mt-6 rounded-3xl bg-slate-50 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Status Update</p>
                        <p class="mt-3 text-2xl font-semibold text-slate-900">
                            {{ isRunning ? 'Update sedang berjalan' : 'Siap menjalankan update' }}
                        </p>
                        <p class="mt-2 break-all text-sm text-slate-500">
                            {{ systemStatus.updateBatExists ? systemStatus.updateBatPath : 'update.bat belum ditemukan di server.' }}
                        </p>
                        <p v-if="systemStatus.lock?.started_at" class="mt-2 text-sm text-slate-500">
                            Dipicu {{ systemStatus.lock.started_at }} oleh {{ systemStatus.lock.started_by || 'admin' }}
                        </p>
                    </div>

                    <div class="mt-6 flex flex-col gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-2xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                            :disabled="!canRunUpdate"
                            @click="runUpdate"
                        >
                            Jalankan update.bat
                        </button>
                        <p v-if="!gitClean" class="text-sm text-amber-700">
                            Tombol update dinonaktifkan sementara karena repository masih punya perubahan lokal.
                        </p>

                        <div class="rounded-3xl border border-amber-100 bg-amber-50/70 p-4">
                            <p class="text-sm font-semibold text-amber-900">Bersihkan repository lokal</p>
                            <p class="mt-1 text-sm text-amber-800">
                                Gunakan ini kalau ingin menghapus perubahan lokal dulu supaya tombol update bisa langsung dipakai.
                                Hati-hati, aksi hapus file untracked bisa menghilangkan file lokal yang belum masuk Git.
                            </p>

                            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                                <button
                                    type="button"
                                    class="rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm font-semibold text-amber-900 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="isRunning || !hasLocalChanges"
                                    @click="runCleanupAction('restore-tracked', 'Balikkan semua perubahan file tracked ke commit terakhir? Perubahan pada file yang sudah ter-track akan hilang.')"
                                >
                                    Balikkan tracked
                                </button>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="isRunning || !hasLocalChanges"
                                    @click="runCleanupAction('clean-untracked', 'Hapus semua file untracked yang tidak di-ignore? Pastikan file lokal penting, termasuk file konfigurasi, sudah aman.')"
                                >
                                    Hapus untracked
                                </button>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="isRunning || !hasLocalChanges"
                                    @click="runCleanupAction('all', 'Balikkan perubahan tracked lalu hapus untracked? Pastikan semua file lokal yang penting sudah dibackup terlebih dahulu.')"
                                >
                                    Bersihkan semua
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <button
                                type="button"
                                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 transition hover:bg-amber-100"
                                @click="runArtisanAction('down', 'down', 'Aktifkan maintenance mode sekarang?')"
                            >
                                php artisan down
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100"
                                @click="runArtisanAction('up', 'up', 'Nonaktifkan maintenance mode sekarang?')"
                            >
                                php artisan up
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100"
                                @click="runArtisanAction('optimize-clear', 'optimize-clear')"
                            >
                                php artisan optimize:clear
                            </button>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                    <h3 class="text-lg font-semibold text-slate-900">Output Perintah</h3>
                    <p class="mt-1 text-sm text-slate-500">Ringkasan cepat command yang paling sering dipakai saat pengecekan versi.</p>

                    <div class="mt-6 space-y-4">
                        <div
                            v-for="command in commandOutputs"
                            :key="command.label"
                            class="rounded-3xl border border-slate-100 bg-slate-950 p-5 text-white"
                        >
                            <p class="font-mono text-xs uppercase tracking-[0.25em] text-slate-400">{{ command.label }}</p>
                            <pre class="mt-3 overflow-x-auto whitespace-pre-wrap break-words text-sm leading-6 text-slate-200">{{ command.output || '-' }}</pre>
                        </div>
                    </div>
                </section>
            </article>
        </section>

        <section class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Log Update Server</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{
                            isRunning
                                ? 'Log sedang dipantau otomatis tiap 5 detik. Area tampilannya dibatasi supaya panel tetap rapi.'
                                : 'Gunakan Refresh Status untuk memuat ulang log terbaru.'
                        }}
                    </p>
                </div>
                <div class="text-right text-sm text-slate-500">
                    <p class="break-all">{{ systemStatus.logPath }}</p>
                    <p>Update terakhir log: {{ systemStatus.logUpdatedAt || '-' }}</p>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-slate-800 bg-slate-950">
                <div class="flex items-center justify-between gap-3 border-b border-slate-800 px-4 py-3 text-xs uppercase tracking-[0.25em] text-slate-400">
                    <span>Live log preview</span>
                    <span>{{ isRunning ? 'Auto refresh aktif' : 'Manual refresh' }}</span>
                </div>
                <pre
                    class="max-h-[420px] overflow-auto whitespace-pre-wrap break-words px-4 py-4 text-sm leading-6 text-slate-200"
                    style="overflow-wrap:anywhere;"
                >{{ updateLog.tail }}</pre>
            </div>
        </section>
    </div>
</template>
