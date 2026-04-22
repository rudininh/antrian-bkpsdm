<script setup>
import Modal from '@/Components/Modal.vue';
import PublicQueueLayout from '@/Layouts/PublicQueueLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineOptions({
    layout: PublicQueueLayout,
});

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
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

const form = useForm({
    service_code: '',
});
const page = usePage();
const servicePickerOpen = ref(false);
const primaryCall = computed(() => props.liveCalls[0] ?? null);
const secondaryCalls = computed(() => props.liveCalls.slice(1, 4));
const selectedService = computed(() => props.services.find((service) => service.code === form.service_code) ?? null);
const soundEnabled = ref(false);
const speechSupported = typeof window !== 'undefined' && 'speechSynthesis' in window;

let intervalId = null;
let spokenCallSignature = null;
let audioContext = null;

const submit = () => {
    form.post(page.props.urls.publicQueueStore);
};

const selectService = (serviceCode) => {
    form.service_code = serviceCode;
    servicePickerOpen.value = false;
};

const unlockSpeech = async () => {
    if (!speechSupported) {
        return;
    }

    if (!audioContext) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        audioContext = AudioContextClass ? new AudioContextClass() : null;
    }

    if (audioContext?.state === 'suspended') {
        await audioContext.resume();
    }

    const utterance = new SpeechSynthesisUtterance('');
    utterance.volume = 0;
    window.speechSynthesis.speak(utterance);
};

const enableSound = async () => {
    if (!speechSupported) {
        return;
    }

    await unlockSpeech();
    soundEnabled.value = true;
};

const enableSoundOnInteraction = () => {
    void enableSound();
};

const buildAnnouncementTexts = (call) => {
    const ticketNumber = String(call.ticketNumber ?? '').replace('-', ' ');
    const counterName = call.counterName ?? 'receptionist';

    return [
        `Nomor antrian ${ticketNumber}, silakan menuju ${counterName}. Saya ulangi, nomor antrian ${ticketNumber}, silakan menuju ${counterName}.`,
        `Perhatian pian sabarataan, antrian nomor ${ticketNumber} dipanggil gasan datang ka ${counterName}. Ulun handak manyampaikan sakali lagi, antrian nomor ${ticketNumber}, silakan langsung bejalan ka ${counterName} gasan dilayani petugas. Nang mamagang nomor ${ticketNumber}, jangan sampai kada tatangar, langsung haja ka ${counterName} wayah ini jua.`,
        `Attention please, ticket number ${ticketNumber}, kindly make your way to ${counterName}. I repeat, ticket number ${ticketNumber}, please proceed to ${counterName} now for assistance. If you are holding ticket number ${ticketNumber}, our staff are ready to serve you at ${counterName}.`,
    ];
};

const speakCall = (call) => {
    if (!speechSupported || !call || !soundEnabled.value) {
        return;
    }

    const voices = window.speechSynthesis.getVoices();
    const indonesianVoice = voices.find((voice) => voice.lang?.toLowerCase().startsWith('id'));
    const britishVoice = voices.find((voice) => voice.lang?.toLowerCase().startsWith('en-gb'));
    const announcementQueue = buildAnnouncementTexts(call);

    window.speechSynthesis.cancel();

    const speakNext = (index = 0) => {
        if (!announcementQueue[index]) {
            return;
        }

        const utterance = new SpeechSynthesisUtterance(announcementQueue[index]);

        utterance.lang = index === 2 ? (britishVoice?.lang ?? 'en-GB') : (indonesianVoice?.lang ?? 'id-ID');
        utterance.voice = index === 2 ? (britishVoice ?? null) : (indonesianVoice ?? null);
        utterance.rate = index === 0 ? 0.92 : index === 1 ? 0.88 : 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;
        utterance.onend = () => speakNext(index + 1);

        window.speechSynthesis.speak(utterance);
    };

    speakNext();
};

const maybeAnnounceLatestCall = (calls) => {
    const latestCall = calls[0];

    if (!latestCall || !['called', 'serving'].includes(latestCall.status)) {
        return;
    }

    const signature = `${latestCall.id}-${latestCall.calledAt}-${latestCall.status}`;

    if (signature === spokenCallSignature) {
        return;
    }

    spokenCallSignature = signature;
    speakCall(latestCall);
};

onMounted(() => {
    if (speechSupported) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = () => {
            window.speechSynthesis.getVoices();
        };
        void enableSound();
        window.addEventListener('pointerdown', enableSoundOnInteraction, { passive: true });
        window.addEventListener('keydown', enableSoundOnInteraction);
    }

    intervalId = window.setInterval(() => {
        router.reload({
            only: ['services', 'liveCalls', 'summary'],
            preserveScroll: true,
        });
    }, 2000);

    maybeAnnounceLatestCall(props.liveCalls);
});

watch(
    () => props.liveCalls,
    (calls) => {
        maybeAnnounceLatestCall(calls);
    },
    { deep: true },
);

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }

    if (speechSupported) {
        window.speechSynthesis.cancel();
        window.speechSynthesis.onvoiceschanged = null;
        window.removeEventListener('pointerdown', enableSoundOnInteraction);
        window.removeEventListener('keydown', enableSoundOnInteraction);
    }

    audioContext?.close?.();
});
</script>

<template>
    <Head title="Ambil Nomor Antrian" />

    <div class="grid gap-5 xl:grid-cols-[1.04fr_0.96fr] xl:items-start">
        <section class="flex min-h-0 flex-col gap-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.4rem] border border-white/70 bg-white/90 px-4 py-4 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Menunggu</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ summary.waiting ?? 0 }}</p>
                </article>
                <article class="rounded-[1.4rem] border border-white/70 bg-white/90 px-4 py-4 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Dipanggil</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-600">{{ summary.called ?? 0 }}</p>
                </article>
                <article class="rounded-[1.4rem] border border-white/70 bg-white/90 px-4 py-4 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Diproses</p>
                    <p class="mt-2 text-3xl font-semibold text-sky-600">{{ summary.serving ?? 0 }}</p>
                </article>
                <article class="rounded-[1.4rem] border border-slate-900/90 bg-slate-950 px-4 py-4 text-white shadow-[0_20px_60px_-45px_rgba(15,23,42,0.7)]">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-300">Selesai</p>
                    <p class="mt-2 text-3xl font-semibold text-teal-300">{{ summary.completed ?? 0 }}</p>
                </article>
            </div>

            <div class="flex min-h-0 flex-1 flex-col rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)] xl:overflow-hidden">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Ambil Nomor</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">Pilih kelompok layanan lalu terbitkan nomor otomatis.</h2>
                    </div>
                </div>

                <form class="mt-4 flex min-h-0 flex-1 flex-col gap-4" @submit.prevent="submit">
                    <div class="rounded-[1.8rem] border border-slate-200 bg-[linear-gradient(145deg,_rgba(255,255,255,0.94),_rgba(240,253,250,0.98))] p-5 shadow-[0_24px_70px_-50px_rgba(15,23,42,0.5)]">
                        <div class="flex flex-col gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Pilihan Layanan</p>
                                <template v-if="selectedService">
                                    <div class="mt-3 flex items-center gap-3">
                                        <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white">
                                            {{ selectedService.code }}
                                        </span>
                                        <h3 class="text-2xl font-semibold text-slate-950">{{ selectedService.groupTitle }}</h3>
                                    </div>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ selectedService.description }}</p>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span
                                            v-for="item in selectedService.items"
                                            :key="item"
                                            class="rounded-full border border-teal-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700"
                                        >
                                            {{ item }}
                                        </span>
                                    </div>
                                </template>
                                <template v-else>
                                    <h3 class="mt-3 text-2xl font-semibold text-slate-950">Belum ada layanan dipilih.</h3>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        Tekan tombol pilih layanan untuk membuka popup kategori layanan A sampai G, lalu pilih kebutuhan yang sesuai.
                                    </p>
                                </template>
                            </div>

                            <button
                                type="button"
                                class="inline-flex w-full items-center justify-center rounded-[1rem] border border-slate-950 bg-white px-6 py-4 text-base font-semibold text-slate-950 transition hover:bg-slate-50"
                                @click="servicePickerOpen = true"
                            >
                                {{ selectedService ? 'Ubah Layanan' : 'Pilih Layanan' }}
                            </button>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[1.25rem] bg-slate-100 px-4 py-4">
                                <div class="text-sm text-slate-500">Dalam antrean</div>
                                <div class="mt-1 text-2xl font-semibold text-slate-950">{{ selectedService?.waitingCount ?? 0 }}</div>
                            </div>
                            <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-4">
                                <div class="text-sm text-emerald-700">Sedang dipanggil</div>
                                <div class="mt-1 text-2xl font-semibold text-emerald-800">{{ selectedService?.calledCount ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.service_code" class="text-sm font-medium text-rose-600">{{ form.errors.service_code }}</p>

                    <div class="border-t border-slate-100 pt-3">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-[1rem] bg-slate-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing || !form.service_code"
                        >
                            {{ form.processing ? 'Memproses...' : 'Ambil Nomor Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <aside class="flex min-h-0 flex-col gap-4 xl:self-start">
            <div class="flex min-h-0 flex-col rounded-[2rem] border border-white/70 bg-slate-950 p-4 text-white shadow-[0_30px_90px_-50px_rgba(15,23,42,0.8)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">Monitor Publik</p>
                    </div>
                    <div class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-teal-200">
                        Live
                    </div>
                </div>

                <div v-if="primaryCall" class="mt-4 rounded-[1.8rem] border border-amber-300/70 bg-[radial-gradient(circle_at_top,_rgba(251,191,36,0.28),_rgba(15,23,42,0.96)_60%)] p-6 shadow-[0_0_30px_rgba(251,191,36,0.28)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-200">Sedang Dipanggil</p>
                            <div class="mt-4 text-6xl font-semibold tracking-[0.12em] text-white sm:text-7xl">{{ primaryCall.ticketNumber }}</div>
                            <div class="mt-4 text-lg font-medium text-slate-200">{{ primaryCall.serviceName }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold uppercase tracking-[0.22em] text-teal-200">{{ primaryCall.counterName }}</div>
                            <div class="mt-4 inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white">
                                {{ primaryCall.status === 'serving' ? 'Sedang Diproses' : 'Segera Menuju Meja' }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.2rem] bg-white/8 px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-300">Jam Panggil</div>
                            <div class="mt-2 text-2xl font-semibold text-white">{{ primaryCall.calledAt }}</div>
                        </div>
                        <div class="rounded-[1.2rem] bg-white/8 px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-300">Informasi</div>
                            <div class="mt-2 text-base text-slate-100">Tamu dengan nomor ini silakan segera menuju meja yang disebutkan.</div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-4 rounded-[1.6rem] border border-dashed border-white/15 px-5 py-10 text-center text-slate-300">
                    Belum ada nomor yang sedang dipanggil saat ini.
                </div>

                <div v-if="secondaryCalls.length" class="mt-4 grid gap-2.5 sm:grid-cols-3">
                    <div
                        v-for="call in secondaryCalls"
                        :key="call.id"
                        class="rounded-[1.2rem] border border-white/10 bg-white/5 px-3 py-3"
                    >
                        <div class="text-lg font-semibold tracking-[0.08em] text-white">{{ call.ticketNumber }}</div>
                        <div class="mt-1 truncate text-xs text-slate-300">{{ call.serviceName }}</div>
                        <div class="mt-3 flex items-center justify-between gap-2 text-[10px] uppercase tracking-[0.16em] text-teal-200">
                            <span>{{ call.counterName }}</span>
                            <span>{{ call.calledAt }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-2 border-t border-white/10 pt-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white/5 px-3 py-2">
                        <div class="text-[10px] uppercase tracking-[0.18em] text-slate-400">Panduan</div>
                        <div class="mt-1 text-xs text-slate-200">1. Pilih layanan.</div>
                    </div>
                    <div class="rounded-2xl bg-white/5 px-3 py-2">
                        <div class="text-[10px] uppercase tracking-[0.18em] text-slate-400">Panduan</div>
                        <div class="mt-1 text-xs text-slate-200">2. Ambil nomor.</div>
                    </div>
                    <div class="rounded-2xl bg-white/5 px-3 py-2">
                        <div class="text-[10px] uppercase tracking-[0.18em] text-slate-400">Panduan</div>
                        <div class="mt-1 text-xs text-slate-200">3. Dengarkan panggilan.</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <Modal :show="servicePickerOpen" max-width="4xl" panel-class="sm:max-w-[92vw]" @close="servicePickerOpen = false">
        <div class="bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.16),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#ecfeff_100%)] p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Pilih Layanan</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">Satu popup untuk semua kelompok layanan BKPSDM.</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Pilih kelompok layanan yang paling sesuai. Prefix tiket akan mengikuti kategori layanan yang Anda pilih.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    @click="servicePickerOpen = false"
                >
                    Tutup
                </button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="service in services"
                    :key="service.code"
                    type="button"
                    class="text-left rounded-[1.5rem] border p-5 transition"
                    :class="form.service_code === service.code
                        ? 'border-teal-500 bg-white shadow-[0_20px_50px_-40px_rgba(13,148,136,0.7)]'
                        : 'border-white/80 bg-white/90 hover:border-teal-300 hover:bg-white'"
                    @click="selectService(service.code)"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white">
                                    {{ service.code }}
                                </span>
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ service.groupTitle }}</div>
                            </div>
                            <h4 class="mt-3 text-xl font-semibold text-slate-950">{{ service.name }}</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ service.description }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 px-3 py-2 text-center text-xs text-slate-500">
                            <div>Antrean</div>
                            <div class="mt-1 text-lg font-semibold text-slate-950">{{ service.waitingCount }}</div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="item in service.items"
                            :key="item"
                            class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700"
                        >
                            {{ item }}
                        </span>
                    </div>
                </button>
            </div>
        </div>
    </Modal>
</template>
