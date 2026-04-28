<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { acknowledgeQueueAnnouncements, queueAlertMuted, toggleQueueAlertMute } from '@/composables/useQueueAlertVoice';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatWaitingDuration } from '@/utils/queueTiming';

defineOptions({
    layout: DashboardLayout,
});

const props = defineProps({
    summary: Object,
    activeCalls: Array,
    waitingQueues: Array,
    meta: Object,
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.success);
const now = ref(Date.now());
const queueVoiceLabel = computed(() => (queueAlertMuted.value ? 'OFF' : 'ON'));

let intervalId = null;
let clockId = null;

onMounted(() => {
    intervalId = window.setInterval(() => {
        router.reload({
            only: ['summary', 'activeCalls', 'waitingQueues'],
            preserveScroll: true,
        });
    }, 5000);

    clockId = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }

    if (clockId) {
        window.clearInterval(clockId);
    }
});

const liveWaitingQueues = computed(() =>
    (props.waitingQueues ?? []).map((queue, index) => ({
        ...queue,
        position: index + 1,
        live_waiting_label: formatWaitingDuration(queue.queued_at_iso, now.value),
    })),
);

const callQueue = (queueId) => {
    acknowledgeQueueAnnouncements();
    router.post(route('monitoring.call', queueId), {}, { preserveScroll: true });
};

const postAction = (name, queueId) => {
    router.post(route(`monitoring.${name}`, queueId), {}, { preserveScroll: true });
};

const statusClasses = {
    called: 'bg-emerald-100 text-emerald-700',
    serving: 'bg-sky-100 text-sky-700',
    completed: 'bg-slate-200 text-slate-700',
    skipped: 'bg-rose-100 text-rose-700',
};
</script>

<template>
    <Head title="Monitoring" />

    <div class="space-y-6">
        <div
            v-if="flashMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ flashMessage }}
        </div>

        <section class="rounded-[2rem] border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-teal-50 px-5 py-4 shadow-[var(--shadow-panel)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-700">Kontrol Suara</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900">Ada Antrian</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Hidupkan untuk memutar suara berulang, matikan kalau sedang tidak ingin ada pengumuman.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold transition"
                        :class="queueAlertMuted ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-amber-500 text-white hover:bg-amber-600'"
                        :aria-pressed="queueAlertMuted"
                        @click="toggleQueueAlertMute"
                    >
                        {{ queueAlertMuted ? 'Suara OFF' : 'Suara ON' }}
                    </button>

                    <div class="rounded-2xl bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-sm">
                        Status: <span class="font-semibold text-slate-900">{{ queueVoiceLabel }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[var(--shadow-panel)]">
                <p class="text-sm text-slate-500">Menunggu</p>
                <p class="mt-3 text-4xl font-semibold text-slate-900">{{ props.summary.waiting }}</p>
            </article>
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[var(--shadow-panel)]">
                <p class="text-sm text-slate-500">Dipanggil</p>
                <p class="mt-3 text-4xl font-semibold text-emerald-600">{{ props.summary.called }}</p>
            </article>
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[var(--shadow-panel)]">
                <p class="text-sm text-slate-500">Diproses</p>
                <p class="mt-3 text-4xl font-semibold text-sky-600">{{ props.summary.serving }}</p>
            </article>
            <article class="rounded-[2rem] border border-white/70 bg-slate-950 p-5 text-white shadow-[var(--shadow-panel)]">
                <p class="text-sm text-slate-300">Selesai</p>
                <p class="mt-3 text-4xl font-semibold text-teal-300">{{ props.summary.completed }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Panggilan Aktif</h3>
                        <p class="mt-1 text-sm text-slate-500">Refresh otomatis setiap 5 detik.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4">
                    <div
                        v-for="call in props.activeCalls"
                        :key="call.id"
                        class="rounded-3xl border border-slate-100 bg-slate-50 p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">{{ call.counter_name }}</p>
                                <h4 class="mt-3 text-4xl font-black text-slate-900">{{ call.ticket_number }}</h4>
                                <p class="mt-1 text-lg font-semibold text-slate-700">{{ call.service_name }}</p>
                                <p class="mt-4 text-sm text-slate-500">Dipanggil pukul {{ call.called_at }}</p>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-3 lg:justify-end">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses[call.status] ?? 'bg-slate-100 text-slate-700'">
                                    {{ call.status }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button type="button" class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600" @click="postAction('recall', call.queue_id)">
                                Panggil Ulang
                            </button>
                            <button type="button" class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700" @click="postAction('start', call.queue_id)">
                                Proses
                            </button>
                            <button type="button" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700" @click="postAction('complete', call.queue_id)">
                                Selesai
                            </button>
                            <button type="button" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700" @click="postAction('skip', call.queue_id)">
                                Lewati
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Daftar Tunggu</h3>
                    <p class="mt-1 text-sm text-slate-500">Panggil nomor berikutnya dari meja receptionist.</p>
                </div>

                <div class="mt-6 space-y-4">
                    <div
                        v-for="queue in liveWaitingQueues"
                        :key="queue.id"
                        class="rounded-3xl border border-slate-100 bg-slate-50 p-4"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white">
                                        #{{ queue.position }}
                                    </span>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        {{ queue.live_waiting_label }}
                                    </span>
                                </div>
                                <p class="mt-3 text-xl font-semibold text-slate-900">{{ queue.ticket_number }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ queue.service_name }} - Masuk {{ queue.queued_at }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
                                <p class="font-semibold text-slate-900">Menunggu</p>
                                <p>{{ queue.live_waiting_label }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 md:flex-row">
                            <PrimaryButton type="button" @click="callQueue(queue.id)">
                                Panggil ke Receptionist
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
