<script setup>
import { Head } from '@inertiajs/vue3';
import StatCard from '@/Components/dashboard/StatCard.vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({
    layout: DashboardLayout,
});

defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
    queues: {
        type: Array,
        default: () => [],
    },
    serviceBreakdown: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const statusClasses = {
    Dipanggil: 'bg-emerald-100 text-emerald-700',
    Menunggu: 'bg-amber-100 text-amber-700',
    Diproses: 'bg-sky-100 text-sky-700',
    Selesai: 'bg-slate-200 text-slate-700',
    Terlewati: 'bg-rose-100 text-rose-700',
    Batal: 'bg-slate-200 text-slate-700',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="item in stats"
                :key="item.label"
                :label="item.label"
                :value="item.value"
                :change="item.change"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Antrian Terkini</h3>
                        <p class="mt-1 text-sm text-slate-500">Data terbaru yang diambil langsung dari tabel antrian dan panggilan hari ini.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                        Live Queue
                    </span>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Nomor</th>
                                <th class="px-5 py-4">Layanan</th>
                                <th class="px-5 py-4">Petugas</th>
                                <th class="px-5 py-4">Jam</th>
                                <th class="px-5 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            <tr v-for="queue in queues" :key="queue.ticket">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ queue.ticket }}</td>
                                <td class="px-5 py-4">{{ queue.service }}</td>
                                <td class="px-5 py-4">{{ queue.desk }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ queue.queued_at }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="statusClasses[queue.status] ?? 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ queue.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="space-y-6">
                <article class="rounded-[2rem] border border-white/70 bg-slate-950 p-6 text-white shadow-[var(--shadow-panel)]">
                    <p class="text-sm uppercase tracking-[0.25em] text-teal-300">Insight Hari Ini</p>
                    <h3 class="mt-4 text-3xl font-semibold">Arus layanan terbaca langsung dari operasional satu meja receptionist.</h3>
                    <p class="mt-4 text-sm leading-6 text-slate-300">
                        Statistik pada kartu ringkasan dihitung dari antrian hari ini, panggilan receptionist, dan histori layanan yang sudah selesai.
                    </p>
                </article>

                <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                    <h3 class="text-lg font-semibold text-slate-900">Distribusi Layanan</h3>
                    <div class="mt-5 space-y-4">
                        <div
                            v-for="service in serviceBreakdown"
                            :key="service.service"
                            class="rounded-2xl bg-slate-50 p-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-slate-900">{{ service.service }}</p>
                                    <p class="text-sm text-slate-500">Jumlah antrian hari ini</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-teal-700 shadow-sm">
                                    {{ service.total }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
