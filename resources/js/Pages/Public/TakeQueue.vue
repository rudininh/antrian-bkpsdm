<script setup>
import PublicQueueLayout from '@/Layouts/PublicQueueLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

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
    service_id: '',
});
const page = usePage();

const submit = () => {
    form.post(page.props.urls.publicQueueStore);
};
</script>

<template>
    <Head title="Ambil Nomor Antrian" />

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <section class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Pilih Layanan</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Nomor terbit otomatis sesuai layanan yang Anda pilih.</h2>
                    </div>
                    <p class="max-w-md text-sm leading-6 text-slate-500">
                        Setelah menekan tombol ambil nomor, Anda akan diarahkan ke halaman sukses yang menampilkan tiket antrian.
                    </p>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <label
                        v-for="service in services"
                        :key="service.id"
                        class="group block cursor-pointer rounded-[1.5rem] border px-5 py-5 transition"
                        :class="form.service_id === service.id ? 'border-teal-500 bg-teal-50 shadow-sm' : 'border-slate-200 bg-white hover:border-teal-300 hover:bg-slate-50'"
                    >
                        <input v-model="form.service_id" type="radio" class="sr-only" :value="service.id" />
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white">
                                        {{ service.code }}
                                    </span>
                                    <h3 class="text-xl font-semibold text-slate-950">{{ service.name }}</h3>
                                </div>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ service.description }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl bg-slate-100 px-4 py-3">
                                    <div class="text-slate-500">Dalam antrean</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-950">{{ service.waitingCount }}</div>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 px-4 py-3">
                                    <div class="text-emerald-700">Sedang dipanggil</div>
                                    <div class="mt-1 text-lg font-semibold text-emerald-800">{{ service.calledCount }}</div>
                                </div>
                            </div>
                        </div>
                    </label>

                    <p v-if="form.errors.service_id" class="text-sm font-medium text-rose-600">{{ form.errors.service_id }}</p>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing || !form.service_id"
                        >
                            {{ form.processing ? 'Memproses...' : 'Ambil Nomor Sekarang' }}
                        </button>
                        <span class="text-sm text-slate-500">Tiket akan dibuat untuk tanggal operasional hari ini.</span>
                    </div>
                </form>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-[2rem] border border-white/70 bg-slate-950 p-6 text-white shadow-[0_30px_90px_-50px_rgba(15,23,42,0.8)]">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-300">Live Panggilan</p>
                <div class="mt-5 space-y-3">
                    <div
                        v-for="call in liveCalls"
                        :key="call.id"
                        class="rounded-[1.5rem] border border-white/10 bg-white/5 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold">{{ call.ticketNumber }}</div>
                                <div class="mt-1 text-sm text-slate-300">{{ call.serviceName }}</div>
                            </div>
                            <div class="text-right text-xs uppercase tracking-[0.18em] text-teal-300">
                                <div>{{ call.counterName }}</div>
                                <div class="mt-2 rounded-full bg-white/10 px-3 py-1 text-[11px]">
                                    {{ call.status === 'serving' ? 'Diproses' : 'Dipanggil' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!liveCalls.length" class="rounded-[1.5rem] border border-dashed border-white/15 p-4 text-sm text-slate-300">
                        Belum ada nomor yang sedang dipanggil saat ini.
                    </p>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)]">
                <h3 class="text-lg font-semibold text-slate-950">Cara Mengambil Antrian</h3>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <p>1. Pilih layanan yang sesuai kebutuhan Anda.</p>
                    <p>2. Simpan nomor tiket yang muncul di halaman sukses.</p>
                    <p>3. Pantau panggilan dari monitor publik atau tunggu arahan petugas.</p>
                </div>
            </div>
        </aside>
    </div>
</template>
