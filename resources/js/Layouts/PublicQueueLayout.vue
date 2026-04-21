<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const urls = computed(() => page.props.urls ?? {});
const publicPage = computed(() => page.props.publicPage ?? {});
</script>

<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.2),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#ecfeff_45%,_#f8fafc_100%)] text-slate-900">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <section class="mb-6 rounded-[2rem] border border-white/70 bg-white/90 px-5 py-5 shadow-[0_24px_70px_-50px_rgba(15,23,42,0.5)] sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-24 w-24 items-center justify-center rounded-[1.5rem] border border-slate-200 bg-slate-50 p-3 shadow-inner">
                        <img src="/images/logo-kota-banjarmasin.png" alt="Logo Kota Banjarmasin" class="h-full w-full object-contain" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-700">Pemerintah Kota Banjarmasin</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
                            Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kota Banjarmasin
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 sm:text-base">
                            Layanan antrian publik BKPSDM Kota Banjarmasin untuk memudahkan tamu memilih layanan, mengambil nomor, dan memantau panggilan secara langsung.
                        </p>
                    </div>
                </div>
            </section>

            <header class="rounded-[2rem] border border-white/70 bg-white/80 px-5 py-5 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)] backdrop-blur sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <Link :href="urls.home" class="text-xs font-semibold uppercase tracking-[0.35em] text-teal-700">
                            Antrian BKPSDM
                        </Link>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ publicPage.title }}</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ publicPage.subtitle }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="urls.publicQueueIndex"
                            class="rounded-full border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-800 transition hover:bg-teal-100"
                        >
                            Ambil Nomor
                        </Link>
                        <Link
                            v-if="user"
                            :href="urls.dashboard"
                            class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Dashboard
                        </Link>
                        <Link
                            v-else
                            :href="urls.login"
                            class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Login Petugas
                        </Link>
                    </div>
                </div>
            </header>

            <main class="mt-6">
                <slot />
            </main>
        </div>
    </div>
</template>
