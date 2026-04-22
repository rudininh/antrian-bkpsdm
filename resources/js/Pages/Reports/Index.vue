<script setup>
import { appRoute } from '@/utils/route';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: DashboardLayout,
});

const props = defineProps({
    report: {
        type: Object,
        default: () => ({}),
    },
});

const route = appRoute;
const toneClasses = {
    teal: 'bg-teal-50 text-teal-900 border-teal-100',
    emerald: 'bg-emerald-50 text-emerald-900 border-emerald-100',
    amber: 'bg-amber-50 text-amber-900 border-amber-100',
    sky: 'bg-sky-50 text-sky-900 border-sky-100',
    rose: 'bg-rose-50 text-rose-900 border-rose-100',
    violet: 'bg-violet-50 text-violet-900 border-violet-100',
};

const queueMax = computed(() => Math.max(...(props.report.queueStatus ?? []).map((item) => item.total), 1));
const serviceMax = computed(() => Math.max(...(props.report.serviceBreakdown ?? []).map((item) => item.total), 1));
const ratingMax = computed(() => Math.max(...(props.report.ratingBreakdown ?? []).map((item) => item.total), 1));
const timelineMax = computed(() =>
    Math.max(...(props.report.timeline ?? []).flatMap((item) => [item.queueTotal, item.guestBookTotal]), 1),
);

const percentWidth = (value, max) => `${Math.max(8, Math.round((value / max) * 100))}%`;
</script>

<template>
    <Head title="Laporan Operasional" />

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2.25rem] border border-white/70 bg-slate-950 text-white shadow-[var(--shadow-panel)]">
            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8 lg:py-8">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-teal-200">
                        Laporan Infografis
                    </div>
                    <div>
                        <h1 class="max-w-2xl text-3xl font-semibold leading-tight md:text-4xl">
                            Ringkasan performa antrian dan buku tamu dalam satu panel yang mudah dibaca.
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                            Periode {{ report.range.startLabel }} sampai {{ report.range.endLabel }}. Gunakan filter tanggal untuk mempersempit data, lalu export ke Excel atau PDF.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            :href="route('reports.export.excel', { start: report.range.start, end: report.range.end })"
                            class="rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-300"
                        >
                            Export Excel
                        </a>
                        <a
                            :href="route('reports.export.pdf', { start: report.range.start, end: report.range.end })"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                        >
                            Export PDF
                        </a>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-white/10 bg-white/5 p-5">
                    <form method="get" :action="route('reports.index')" class="space-y-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Filter Tanggal</p>
                            <p class="mt-2 text-sm text-slate-300">Atur rentang laporan sesuai kebutuhan monitoring harian atau bulanan.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-300">Mulai</span>
                                <input
                                    type="date"
                                    name="start"
                                    :value="report.range.start"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none ring-0 transition focus:border-teal-400"
                                >
                            </label>
                            <label class="space-y-2 text-sm">
                                <span class="text-slate-300">Sampai</span>
                                <input
                                    type="date"
                                    name="end"
                                    :value="report.range.end"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none ring-0 transition focus:border-teal-400"
                                >
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-2xl bg-teal-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-teal-300"
                        >
                            Terapkan Filter
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="item in report.kpis"
                :key="item.label"
                class="rounded-[1.75rem] border p-5 shadow-[var(--shadow-panel)]"
                :class="toneClasses[item.tone] ?? 'bg-white text-slate-900 border-white/70'"
            >
                <p class="text-xs uppercase tracking-[0.25em] opacity-70">{{ item.label }}</p>
                <p class="mt-4 text-3xl font-semibold">{{ item.value }}</p>
                <p class="mt-2 text-sm opacity-80">{{ item.note }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Antrian</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Distribusi status antrian</h2>
                        <p class="mt-1 text-sm text-slate-500">Komposisi antrian hari-hari di periode ini, dibaca cepat seperti kartu infografis.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-950 px-4 py-3 text-right text-white">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Top Layanan</p>
                        <p class="mt-1 text-lg font-semibold">{{ report.summary.topService }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <div v-for="item in report.queueStatus" :key="item.status" class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-900">{{ item.label }}</span>
                            <span class="text-slate-500">{{ item.total }} antrian</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-400"
                                :style="{ width: percentWidth(item.total, queueMax) }"
                            ></div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Buku Tamu</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">Rating dan rekomendasi</h2>
                <p class="mt-1 text-sm text-slate-500">Gambaran kepuasan pengunjung dari rating, rekomendasi, dan feedback yang masuk.</p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-slate-950 p-5 text-white">
                        <p class="text-xs uppercase tracking-[0.25em] text-teal-300">Rata-rata Rating</p>
                        <p class="mt-3 text-4xl font-semibold">{{ report.summary.averageRating.toFixed(1) }}</p>
                        <p class="mt-2 text-sm text-slate-300">Skala 1 sampai 5</p>
                    </div>
                    <div class="rounded-3xl border border-slate-100 bg-emerald-50 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-emerald-600">Rekomendasi</p>
                        <p class="mt-3 text-4xl font-semibold text-emerald-800">{{ report.summary.recommendationRate }}%</p>
                        <p class="mt-2 text-sm text-emerald-700">Responden yang menjawab ya</p>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <div v-for="item in report.ratingBreakdown" :key="item.rating" class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-900">Rating {{ item.rating }}</span>
                            <span class="text-slate-500">{{ item.total }} data</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-amber-400 to-rose-400"
                                :style="{ width: percentWidth(item.total, ratingMax) }"
                            ></div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Tren Harian</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Timeline antrian dan buku tamu</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                        {{ report.timeline.length }} hari
                    </span>
                </div>

                <div class="mt-6 space-y-4">
                    <div v-for="item in report.timeline" :key="item.date" class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="font-semibold text-slate-900">{{ item.date }}</span>
                            <div class="flex items-center gap-3 text-slate-500">
                                <span>Antrian {{ item.queueTotal }}</span>
                                <span>Buku tamu {{ item.guestBookTotal }}</span>
                            </div>
                        </div>
                        <div class="mt-3 grid gap-2">
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-teal-500 to-cyan-400"
                                    :style="{ width: percentWidth(item.queueTotal, timelineMax) }"
                                ></div>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-400"
                                    :style="{ width: percentWidth(item.guestBookTotal, timelineMax) }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Layanan</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">Distribusi layanan</h2>
                <p class="mt-1 text-sm text-slate-500">Semakin panjang batangnya, semakin banyak antrian yang masuk ke layanan tersebut.</p>

                <div class="mt-6 space-y-4">
                    <div v-for="item in report.serviceBreakdown" :key="item.service" class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-900">{{ item.service }}</span>
                            <span class="text-slate-500">{{ item.total }} antrian</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-sky-500 to-indigo-400"
                                :style="{ width: percentWidth(item.total, serviceMax) }"
                            ></div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Detail Antrian</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">10 data terbaru</h2>
                    </div>
                    <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white">Queue</span>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-slate-500">
                            <tr>
                                <th class="px-4 py-4">Nomor</th>
                                <th class="px-4 py-4">Layanan</th>
                                <th class="px-4 py-4">Status</th>
                                <th class="px-4 py-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            <tr v-for="queue in report.recentQueues" :key="queue.ticket">
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ queue.ticket }}</td>
                                <td class="px-4 py-4">{{ queue.service }}</td>
                                <td class="px-4 py-4">{{ queue.status }}</td>
                                <td class="px-4 py-4 text-slate-500">{{ queue.queuedAt }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Detail Buku Tamu</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">10 feedback terbaru</h2>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Guest</span>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-slate-500">
                            <tr>
                                <th class="px-4 py-4">Tamu</th>
                                <th class="px-4 py-4">Instansi</th>
                                <th class="px-4 py-4">Rating</th>
                                <th class="px-4 py-4">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            <tr v-for="guest in report.recentGuestBooks" :key="guest.ticket + guest.submittedAt">
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ guest.guestName }}</td>
                                <td class="px-4 py-4">{{ guest.institution }}</td>
                                <td class="px-4 py-4">{{ guest.rating }}</td>
                                <td class="px-4 py-4">{{ guest.wouldRecommend }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</template>
