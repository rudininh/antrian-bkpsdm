<script setup>
import PublicQueueLayout from '@/Layouts/PublicQueueLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineOptions({
    layout: PublicQueueLayout,
});

defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    liveCalls: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const countdown = ref(5);
const redirectLabel = computed(() => `${countdown.value} detik`);
let timeoutId = null;
let intervalId = null;

onMounted(() => {
    intervalId = window.setInterval(() => {
        if (countdown.value > 1) {
            countdown.value -= 1;
        }
    }, 1000);

    router.reload({
        only: ['liveCalls', 'summary'],
        preserveScroll: true,
    });

    timeoutId = window.setTimeout(() => {
        router.visit(page.props.urls.publicQueueIndex);
    }, 5000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }

    if (timeoutId) {
        window.clearTimeout(timeoutId);
    }
});
</script>

<template>
    <Head title="Tiket Antrian" />

    <div class="grid gap-6 xl:grid-cols-[1fr_0.85fr]">
        <section class="rounded-[2rem] border border-white/70 bg-white/92 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)] sm:p-8">
            <div class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                Tiket berhasil diterbitkan
            </div>

            <div class="mt-6 rounded-[2rem] bg-[linear-gradient(135deg,_#0f172a,_#115e59)] p-6 text-white sm:p-8">
                <p class="text-sm uppercase tracking-[0.25em] text-teal-200">Nomor Anda</p>
                <div class="mt-4 text-5xl font-semibold tracking-[0.08em] sm:text-6xl">{{ ticket.ticketNumber }}</div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-200">Layanan</div>
                        <div class="mt-2 text-lg font-semibold">{{ ticket.serviceName }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-200">Waktu Ambil</div>
                        <div class="mt-2 text-lg font-semibold">{{ ticket.queueDate }} - {{ ticket.queuedAt }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="text-sm text-slate-500">Posisi antrean aktif di layanan ini</div>
                    <div class="mt-2 text-4xl font-semibold text-slate-950">{{ ticket.queuesAhead }}</div>
                    <p class="mt-2 text-sm text-slate-600">nomor masih berada di depan Anda</p>
                </article>
                <article class="rounded-[1.5rem] border border-teal-200 bg-teal-50 px-5 py-5">
                    <div class="text-sm text-teal-700">Status tiket</div>
                    <div class="mt-2 text-2xl font-semibold text-teal-900">
                        {{ ticket.status === 'waiting' ? 'Menunggu Panggilan' : ticket.status }}
                    </div>
                    <p class="mt-2 text-sm text-teal-800">Halaman ini akan kembali otomatis ke ambil antrian dalam {{ redirectLabel }}.</p>
                </article>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <Link
                    :href="page.props.urls.publicQueueIndex"
                    class="rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Kembali ke Ambil Antrian
                </Link>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                <h3 class="text-lg font-semibold text-slate-950">Ringkasan Operasional</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-100 px-4 py-4">
                        <div class="text-sm text-slate-500">Menunggu</div>
                        <div class="mt-2 text-3xl font-semibold text-slate-950">{{ summary.waiting ?? 0 }}</div>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 px-4 py-4">
                        <div class="text-sm text-emerald-700">Dipanggil</div>
                        <div class="mt-2 text-3xl font-semibold text-emerald-800">{{ summary.called ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-slate-950 p-6 text-white shadow-[0_30px_90px_-50px_rgba(15,23,42,0.8)]">
                <h3 class="text-lg font-semibold">Panggilan Saat Ini</h3>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="call in liveCalls"
                        :key="call.id"
                        class="rounded-[1.5rem] border border-white/10 bg-white/5 p-4"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-2xl font-semibold">{{ call.ticketNumber }}</div>
                                <div class="mt-1 text-sm text-slate-300">{{ call.serviceName }}</div>
                            </div>
                            <div class="text-right text-xs uppercase tracking-[0.18em] text-teal-300">
                                <div>{{ call.counterName }}</div>
                                <div class="mt-2">{{ call.calledAt }}</div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!liveCalls.length" class="text-sm text-slate-300">Belum ada panggilan aktif saat ini.</p>
                </div>
            </div>
        </aside>
    </div>
</template>
