<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';

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

let intervalId = null;

onMounted(() => {
    intervalId = window.setInterval(() => {
        router.reload({
            only: ['summary', 'activeCalls', 'waitingQueues'],
            preserveScroll: true,
        });
    }, 5000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }
});

const callQueue = (queueId) => {
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

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div
                        v-for="call in props.activeCalls"
                        :key="call.id"
                        class="rounded-3xl border border-slate-100 bg-slate-50 p-5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ call.counter_name }}</p>
                                <h4 class="mt-3 text-3xl font-semibold text-slate-900">{{ call.ticket_number }}</h4>
                                <p class="mt-1 text-sm text-slate-600">{{ call.service_name }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses[call.status] ?? 'bg-slate-100 text-slate-700'">
                                {{ call.status }}
                            </span>
                        </div>

                        <p class="mt-4 text-sm text-slate-500">Dipanggil pukul {{ call.called_at }}</p>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" class="text-sm font-medium text-amber-700 hover:text-amber-900" @click="postAction('recall', call.queue_id)">
                                Panggil Ulang
                            </button>
                            <button type="button" class="text-sm font-medium text-sky-700 hover:text-sky-900" @click="postAction('start', call.queue_id)">
                                Proses
                            </button>
                            <button type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900" @click="postAction('complete', call.queue_id)">
                                Selesai
                            </button>
                            <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-800" @click="postAction('skip', call.queue_id)">
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
                        v-for="queue in props.waitingQueues"
                        :key="queue.id"
                        class="rounded-3xl border border-slate-100 bg-slate-50 p-4"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xl font-semibold text-slate-900">{{ queue.ticket_number }}</p>
                                <p class="text-sm text-slate-500">{{ queue.service_name }} - {{ queue.queued_at }}</p>
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
