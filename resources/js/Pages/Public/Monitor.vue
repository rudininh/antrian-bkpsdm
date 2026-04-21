<script setup>
import PublicQueueLayout from '@/Layouts/PublicQueueLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

defineOptions({
    layout: PublicQueueLayout,
});

const props = defineProps({
    liveCalls: {
        type: Array,
        default: () => [],
    },
    recentlyCompleted: {
        type: Array,
        default: () => [],
    },
    waitingByService: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

let intervalId = null;
const page = usePage();

onMounted(() => {
    intervalId = window.setInterval(() => {
        router.reload({
            only: ['liveCalls', 'recentlyCompleted', 'waitingByService', 'summary'],
            preserveScroll: true,
        });
    }, 5000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }
});
</script>

<template>
    <Head title="Monitor Publik" />

    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                <p class="text-sm text-slate-500">Menunggu</p>
                <p class="mt-3 text-4xl font-semibold text-slate-950">{{ summary.waiting ?? 0 }}</p>
            </article>
            <article class="rounded-[1.75rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                <p class="text-sm text-slate-500">Dipanggil</p>
                <p class="mt-3 text-4xl font-semibold text-emerald-600">{{ summary.called ?? 0 }}</p>
            </article>
            <article class="rounded-[1.75rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                <p class="text-sm text-slate-500">Diproses</p>
                <p class="mt-3 text-4xl font-semibold text-sky-600">{{ summary.serving ?? 0 }}</p>
            </article>
            <article class="rounded-[1.75rem] border border-slate-900/90 bg-slate-950 p-5 text-white shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                <p class="text-sm text-slate-300">Selesai Hari Ini</p>
                <p class="mt-3 text-4xl font-semibold text-teal-300">{{ summary.completed ?? 0 }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-[2rem] border border-white/70 bg-slate-950 p-6 text-white shadow-[0_30px_90px_-50px_rgba(15,23,42,0.8)]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">Sedang Dipanggil</p>
                        <h2 class="mt-2 text-2xl font-semibold">Layar monitor akan menyegarkan data setiap 5 detik.</h2>
                    </div>
                    <Link
                        :href="page.props.urls.publicQueueIndex"
                        class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        Ambil Nomor
                    </Link>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div
                        v-for="call in props.liveCalls"
                        :key="call.id"
                        class="rounded-[1.75rem] border border-white/10 bg-white/5 p-5"
                    >
                        <div class="text-xs uppercase tracking-[0.24em] text-teal-300">{{ call.counterName }}</div>
                        <div class="mt-4 text-4xl font-semibold tracking-[0.08em]">{{ call.ticketNumber }}</div>
                        <div class="mt-2 text-sm text-slate-300">{{ call.serviceName }}</div>
                        <div class="mt-5 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white">
                            {{ call.status === 'serving' ? 'Diproses' : 'Dipanggil' }} - {{ call.calledAt }}
                        </div>
                    </div>

                    <div
                        v-if="!props.liveCalls.length"
                        class="rounded-[1.75rem] border border-dashed border-white/15 p-5 text-sm text-slate-300 md:col-span-2"
                    >
                        Belum ada nomor aktif yang sedang dipanggil.
                    </div>
                </div>
            </article>

            <div class="space-y-6">
                <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                    <h3 class="text-lg font-semibold text-slate-950">Antrean per Layanan</h3>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="service in props.waitingByService"
                            :key="service.id"
                            class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-semibold text-slate-950">{{ service.name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">Nomor berikutnya: {{ service.nextTicket ?? '-' }}</div>
                                </div>
                                <div class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-slate-950">
                                    {{ service.waiting }} aktif
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                    <h3 class="text-lg font-semibold text-slate-950">Baru Selesai</h3>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="item in props.recentlyCompleted"
                            :key="item.id"
                            class="rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-semibold text-slate-950">{{ item.ticketNumber }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ item.serviceName }} - {{ item.counterName }}</div>
                                </div>
                                <div class="text-sm font-medium text-slate-500">{{ item.finishedAt }}</div>
                            </div>
                        </div>
                        <p v-if="!props.recentlyCompleted.length" class="text-sm text-slate-500">Belum ada layanan yang selesai tercatat.</p>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
