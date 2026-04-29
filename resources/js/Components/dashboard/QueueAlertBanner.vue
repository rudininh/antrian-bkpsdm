<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatWaitingDuration } from '@/utils/queueTiming';

const props = defineProps({
    queueAlert: {
        type: Object,
        default: () => ({}),
    },
    muted: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle-mute']);

const hasWaiting = computed(() => Number(props.queueAlert?.waitingCount ?? 0) > 0);
const now = ref(Date.now());
let clockId = null;

onMounted(() => {
    clockId = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onBeforeUnmount(() => {
    if (clockId) {
        window.clearInterval(clockId);
    }
});

const liveWaitingLabel = computed(() =>
    formatWaitingDuration(props.queueAlert?.nextQueuedAtIso, now.value),
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="-translate-y-3 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-3 opacity-0"
    >
        <div
            v-if="hasWaiting"
            class="mt-4 overflow-hidden rounded-[2rem] border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-teal-50 px-5 py-4 shadow-[var(--shadow-panel)]"
            role="status"
            aria-live="polite"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-sm">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5" />
                            <path d="M9 17a3 3 0 0 0 6 0" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-700">Ada antrian menunggu</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            {{ queueAlert.waitingCount }} nomor sedang menunggu dipanggil
                        </h3>
                        <p class="mt-1 text-sm text-slate-600">
                            <span v-if="queueAlert.nextTicketNumber">
                                Berikutnya: <span class="font-semibold text-slate-900">{{ queueAlert.nextTicketNumber }}</span>
                                <span v-if="queueAlert.nextServiceName"> - {{ queueAlert.nextServiceName }}</span>
                                <span class="font-medium text-slate-500"> - menunggu {{ liveWaitingLabel }}</span>
                            </span>
                            <span v-else>Silakan buka monitoring untuk melihat detail antrean terbaru.</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-2xl bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-sm">
                        <div class="font-semibold text-slate-900">Total menunggu</div>
                        <div>{{ queueAlert.waitingCount }}</div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border px-4 py-2 text-sm font-semibold transition"
                        :class="muted ? 'border-slate-300 bg-slate-100 text-slate-700 hover:bg-slate-200' : 'border-amber-200 bg-amber-100 text-amber-800 hover:bg-amber-200'"
                        :aria-pressed="muted"
                        @click="emit('toggle-mute')"
                    >
                        <span v-if="muted">Aktifkan Suara</span>
                        <span v-else>Mute Suara</span>
                    </button>

                    <Link
                        :href="route('monitoring.index')"
                        class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Buka Monitoring
                    </Link>
                </div>
            </div>
        </div>
    </Transition>
</template>
