<script setup>
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PublicQueueLayout from '@/Layouts/PublicQueueLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineOptions({
    layout: PublicQueueLayout,
});

const STAFF_NAMES = [
    'Totok Agus Daryanto, M.Pd',
    'Rahmasari, S.Pi',
    'Mouna Rahmawati, S.Psi',
    'Untung Eko Laksono, S.H., M.Kn.',
    'M. Yusri Zani, S.Pd., M.Pd',
    'Hj. Ellis Surialda, SE',
    'Tinton Aditya Ramadhan, SE',
    'Hj. Rahmawati, SE',
    'Setia Rahayuningsih, S.STP., M.M.',
    'Budi Rahmadi, SE',
    'Hj. Mutia Anwary, SH, MH',
    'Firmansyah, SE',
    'Yanuar Norianto, S.A.P',
    'Ahmad Taufik Gunawan, S.Sos',
    'Husnul Abdi, S.Sos, M.AB',
    'Hj. Ariyanti, S.P.,M.Pd',
    'Erza Fikriani Rahmah, SM',
    'Muhammad Januar Irhandy, S.AP',
    'Faten Hamama, S.Psi',
    'Rony Yuni, A.Md',
    'Kartika Anggraini, A.Md',
    'Marliansyah, A.Md',
    'Norma Yulianti, A.Md',
    'Novia Awaliah, S.Ak',
    'Nahlia Budiyanti, S.Kom',
    'Dewi Hijriatul Fitri, SE',
    'Rusdiati, S.Sos',
    'Herni Octavia Eriani, S. AP., M.M.',
    'Nyimas Rayna Nuril, SH',
    'Rezwity Nurrazanah , SE',
    'Ardiansyah, SE',
    'Duhita Agsha Ayudya, S.Kom',
    'Ritli Rizqiana Maula, S.Psi',
    'Kusuma Indra, SE',
    'Fakhrizal Rusadi, S.Kom, MM',
    'Hendry Ervin Noor Ridwan, ST',
    'Muhammad Ryanda Faza Fadhila, S.Tr.IP',
    'Anandya Dewanga, S.Tr.I.P',
    'Maulidinoor, A.Md',
    'Aulia Nurmeidha, A.Md',
    'Muhammad Abidin, A.Md',
    'Rudini Nor Habibi, A.Md',
    'Heldawaty, A.Md',
    'Citra Aulia, A.Md',
    'M. Athoilah, A.Md',
    'Jurnalinda, A.Md',
    'Ronny Satria',
    'Supardi',
    'Ahya Azizah',
    'KUSNAWATI',
    'AISYA AGUSTINA',
];

const props = defineProps({
    activeQueue: {
        type: Object,
        default: null,
    },
    options: {
        type: Object,
        default: () => ({}),
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const statusLabel = computed(() => props.options.statuses?.[props.activeQueue?.status] ?? props.activeQueue?.status ?? '-');
const currentQueueId = ref(props.activeQueue?.id ?? null);
const consultantPickerOpen = ref(false);
const consultantSearch = ref('');
let intervalId = null;

const filteredStaff = computed(() => {
    const keyword = consultantSearch.value.trim().toLowerCase();

    if (!keyword) {
        return STAFF_NAMES;
    }

    return STAFF_NAMES.filter((name) => name.toLowerCase().includes(keyword));
});

const form = useForm({
    queue_id: props.activeQueue?.id ?? null,
    guest_name: props.activeQueue?.guestBook?.guestName ?? '',
    institution: props.activeQueue?.guestBook?.institution ?? '',
    phone_number: props.activeQueue?.guestBook?.phoneNumber ?? '',
    visit_purpose: props.activeQueue?.guestBook?.visitPurpose ?? '',
    consultant_name: props.activeQueue?.guestBook?.consultantName ?? '',
    rating: props.activeQueue?.guestBook?.rating ?? null,
    feedback: props.activeQueue?.guestBook?.feedback ?? '',
    would_recommend: props.activeQueue?.guestBook?.wouldRecommend ?? null,
});

const setRecommend = (value) => {
    form.would_recommend = value;
};

const pickConsultant = (name) => {
    form.consultant_name = name;
    consultantPickerOpen.value = false;
};

const hydrateFormFromQueue = (queue) => {
    form.queue_id = queue?.id ?? null;
    form.guest_name = queue?.guestBook?.guestName ?? '';
    form.institution = queue?.guestBook?.institution ?? '';
    form.phone_number = queue?.guestBook?.phoneNumber ?? '';
    form.visit_purpose = queue?.guestBook?.visitPurpose ?? '';
    form.consultant_name = queue?.guestBook?.consultantName ?? '';
    form.rating = queue?.guestBook?.rating ?? null;
    form.feedback = queue?.guestBook?.feedback ?? '';
    form.would_recommend = queue?.guestBook?.wouldRecommend ?? null;
    consultantSearch.value = '';
};

const reloadWithFallback = () => {
    router.reload({
        only: ['activeQueue', 'meta'],
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            window.location.reload();
        },
    });
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        rating: data.rating ? Number(data.rating) : null,
        would_recommend: data.would_recommend === null || data.would_recommend === '' ? null : Boolean(data.would_recommend),
    })).post(route('public.guest-book.kiosk.store'), {
        preserveScroll: true,
    });
};

watch(
    () => props.activeQueue,
    (queue) => {
        if (!queue?.id) {
            currentQueueId.value = null;
            hydrateFormFromQueue(null);
            return;
        }

        if (queue.id !== currentQueueId.value) {
            currentQueueId.value = queue.id;
            hydrateFormFromQueue(queue);
        }
    },
    { deep: true },
);

onMounted(() => {
    intervalId = window.setInterval(reloadWithFallback, 3000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }
});
</script>

<template>
    <Head title="Buku Tamu" />

    <div class="w-full">
        <section class="w-full rounded-[2rem] border border-white/70 bg-white/92 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
            <h1 class="mt-2 text-2xl font-semibold text-slate-950 sm:text-3xl">{{ meta.title }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ meta.description }}</p>

            <p v-if="page.props.flash.success" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ page.props.flash.success }}
            </p>

            <div class="mt-5 rounded-[1.4rem] border border-slate-200 bg-slate-50 p-4">
                <template v-if="activeQueue">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Nomor Aktif</div>
                            <div class="mt-1 text-3xl font-semibold text-slate-950">{{ activeQueue.ticketNumber }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Status</div>
                            <div class="mt-1 text-lg font-semibold text-teal-700">{{ statusLabel }}</div>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-slate-500">Dipanggil {{ activeQueue.calledAt || '-' }}</div>
                </template>
                <template v-else>
                    <p class="text-sm font-medium text-slate-700">Belum ada nomor aktif yang dipanggil saat ini.</p>
                    <p class="mt-1 text-sm text-slate-500">Form akan aktif otomatis saat ada nomor berstatus dipanggil atau sedang diproses.</p>
                </template>
            </div>

            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <input v-model="form.queue_id" type="hidden" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Layanan Terpilih</label>
                        <input :value="activeQueue?.serviceName ?? '-'" type="text" class="mt-1 w-full rounded-xl border-slate-300 bg-slate-100 text-sm text-slate-700 shadow-sm" disabled />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nama Tamu</label>
                        <input v-model="form.guest_name" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" :disabled="!activeQueue || form.processing" />
                        <InputError class="mt-2" :message="form.errors.guest_name" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Instansi / Unit</label>
                        <input v-model="form.institution" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" :disabled="!activeQueue || form.processing" />
                        <InputError class="mt-2" :message="form.errors.institution" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nomor HP</label>
                        <input v-model="form.phone_number" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" :disabled="!activeQueue || form.processing" />
                        <InputError class="mt-2" :message="form.errors.phone_number" />
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Nama Konsultan / Pegawai yang Melayani</label>
                    <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                        <input v-model="form.consultant_name" type="text" class="w-full rounded-xl border-slate-300 bg-slate-100 text-sm text-slate-700 shadow-sm" placeholder="Belum dipilih" disabled />
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-teal-300 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-800 transition hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!activeQueue || form.processing" @click="consultantPickerOpen = true">
                            Pilih Nama Pegawai
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.consultant_name" />
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Detail Permasalahan</label>
                    <textarea v-model="form.visit_purpose" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" :disabled="!activeQueue || form.processing" />
                    <InputError class="mt-2" :message="form.errors.visit_purpose" />
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Rating Pelayanan (1-5)</label>
                    <select v-model="form.rating" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" :disabled="!activeQueue || form.processing">
                        <option :value="null">Belum memberi rating</option>
                        <option :value="1">1 - Sangat kurang</option>
                        <option :value="2">2 - Kurang</option>
                        <option :value="3">3 - Cukup</option>
                        <option :value="4">4 - Baik</option>
                        <option :value="5">5 - Sangat baik</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.rating" />
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Rekomendasi Layanan</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="form.would_recommend === true ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'" :disabled="!activeQueue || form.processing" @click="setRecommend(true)">Ya</button>
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="form.would_recommend === false ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'" :disabled="!activeQueue || form.processing" @click="setRecommend(false)">Tidak</button>
                        <button type="button" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="form.would_recommend === null ? 'border-slate-500 bg-slate-100 text-slate-800' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'" :disabled="!activeQueue || form.processing" @click="setRecommend(null)">Lewati</button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.would_recommend" />
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Saran / Catatan</label>
                    <textarea v-model="form.feedback" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" :disabled="!activeQueue || form.processing" />
                    <InputError class="mt-2" :message="form.errors.feedback" />
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-[1rem] bg-slate-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!activeQueue || form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </form>
        </section>
    </div>

    <Modal :show="consultantPickerOpen" max-width="4xl" panel-class="sm:max-w-[92vw]" @close="consultantPickerOpen = false">
        <div class="bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.16),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#ecfeff_100%)] p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Pilih Pegawai</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">Nama konsultan / pegawai yang melayani</h3>
                </div>
                <button type="button" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="consultantPickerOpen = false">
                    Tutup
                </button>
            </div>

            <div class="mt-4">
                <input v-model="consultantSearch" type="text" placeholder="Cari nama pegawai..." class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>

            <div class="mt-4 max-h-[55vh] space-y-2 overflow-y-auto pr-1">
                <button
                    v-for="name in filteredStaff"
                    :key="name"
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-800 transition hover:border-teal-300 hover:bg-teal-50"
                    @click="pickConsultant(name)"
                >
                    <span>{{ name }}</span>
                    <span class="text-xs uppercase tracking-[0.2em] text-teal-700">Pilih</span>
                </button>
                <div v-if="!filteredStaff.length" class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">
                    Nama tidak ditemukan.
                </div>
            </div>
        </div>
    </Modal>
</template>
