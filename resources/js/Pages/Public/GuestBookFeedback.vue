<script setup>
import PublicQueueLayout from '@/Layouts/PublicQueueLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: PublicQueueLayout,
});

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    guestBook: {
        type: Object,
        default: () => ({}),
    },
    options: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const statusLabel = computed(() => props.options.statuses?.[props.ticket.status] ?? props.ticket.status);

const form = useForm({
    guest_name: props.guestBook.guestName ?? '',
    institution: props.guestBook.institution ?? '',
    phone_number: props.guestBook.phoneNumber ?? '',
    visit_purpose: props.guestBook.visitPurpose ?? '',
    rating: props.guestBook.rating ?? null,
    feedback: props.guestBook.feedback ?? '',
    would_recommend: props.guestBook.wouldRecommend ?? null,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        rating: data.rating ? Number(data.rating) : null,
        would_recommend: data.would_recommend === null || data.would_recommend === '' ? null : Boolean(data.would_recommend),
    })).put(route('public.guest-book.upsert', props.ticket.id), {
        preserveScroll: true,
    });
};

const setRecommend = (value) => {
    form.would_recommend = value;
};
</script>

<template>
    <Head title="Buku Tamu & Feedback" />

    <div class="mx-auto max-w-5xl space-y-5">
        <section class="rounded-[2rem] border border-white/70 bg-white/92 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-700">Buku Tamu & Feedback</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-950 sm:text-3xl">Isi data tamu dan pengalaman layanan pada tiket ini.</h1>
                </div>
                <div class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                    {{ ticket.ticketNumber }}
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-100 px-4 py-3">
                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Layanan</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ ticket.serviceName }}</div>
                </div>
                <div class="rounded-2xl bg-slate-100 px-4 py-3">
                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Waktu Ambil</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ ticket.queueDate }} - {{ ticket.queuedAt }}</div>
                </div>
                <div class="rounded-2xl bg-teal-50 px-4 py-3">
                    <div class="text-xs uppercase tracking-[0.18em] text-teal-700">Status Tiket</div>
                    <div class="mt-1 text-sm font-semibold text-teal-900">{{ statusLabel }}</div>
                </div>
            </div>

            <p v-if="page.props.flash.success" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ page.props.flash.success }}
            </p>
        </section>

        <form class="space-y-5" @submit.prevent="submit">
            <section class="rounded-[2rem] border border-white/70 bg-white/92 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                <h2 class="text-xl font-semibold text-slate-950">Buku Tamu</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Nama Tamu</label>
                        <input v-model="form.guest_name" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                        <InputError class="mt-2" :message="form.errors.guest_name" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Instansi / Unit</label>
                        <input v-model="form.institution" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                        <InputError class="mt-2" :message="form.errors.institution" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nomor HP</label>
                        <input v-model="form.phone_number" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                        <InputError class="mt-2" :message="form.errors.phone_number" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Tujuan Kunjungan</label>
                        <textarea v-model="form.visit_purpose" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                        <InputError class="mt-2" :message="form.errors.visit_purpose" />
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/70 bg-white/92 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                <h2 class="text-xl font-semibold text-slate-950">Feedback Layanan</h2>
                <div class="mt-4 grid gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Rating Pelayanan (1-5)</label>
                        <select v-model="form.rating" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                        <label class="text-sm font-medium text-slate-700">Apakah Anda merekomendasikan layanan ini?</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                                :class="form.would_recommend === true ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                                @click="setRecommend(true)"
                            >
                                Ya
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                                :class="form.would_recommend === false ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                                @click="setRecommend(false)"
                            >
                                Tidak
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                                :class="form.would_recommend === null ? 'border-slate-500 bg-slate-100 text-slate-800' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                                @click="setRecommend(null)"
                            >
                                Lewati
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.would_recommend" />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Saran / Catatan</label>
                        <textarea v-model="form.feedback" rows="4" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                        <InputError class="mt-2" :message="form.errors.feedback" />
                    </div>
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-[1rem] bg-slate-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Buku Tamu + Feedback' }}
                </button>
            </div>
        </form>

        <section class="flex flex-wrap gap-3">
            <Link
                :href="route('public.queue.success', ticket.id)"
                class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Kembali ke Tiket
            </Link>
            <Link
                :href="page.props.urls.publicQueueIndex"
                class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Ke Ambil Antrian
            </Link>
        </section>
    </div>
</template>
