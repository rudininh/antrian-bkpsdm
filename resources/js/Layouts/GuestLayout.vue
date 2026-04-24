<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const urls = computed(() => page.props.urls ?? {});
</script>

<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.18),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#ecfeff_45%,_#f8fafc_100%)] px-4 py-8 text-slate-900 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl flex-col justify-between">
            <section class="mb-6 rounded-[2rem] border border-white/70 bg-white/90 px-5 py-5 shadow-[0_24px_70px_-50px_rgba(15,23,42,0.5)] sm:px-6">
                <div class="flex items-center gap-4">
                    <Link
                        href="/"
                        class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] border border-slate-200 bg-slate-50 p-3 shadow-inner"
                    >
                        <img :src="urls.logoKotaBanjarmasin" alt="Logo Kota Banjarmasin" class="h-full w-full object-contain" />
                    </Link>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-700">Pemerintah Kota Banjarmasin</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
                            Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kota Banjarmasin
                        </h2>
                    </div>
                </div>
            </section>

            <main class="flex items-center justify-center">
                <div class="w-full overflow-hidden rounded-[2rem] border border-white/70 bg-white/95 px-6 py-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.55)] sm:max-w-md sm:px-8 sm:py-8">
                    <slot />
                </div>
            </main>

            <footer class="mt-6 rounded-[1.5rem] border border-white/70 bg-white/85 px-5 py-4 text-sm text-slate-600 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.45)] sm:px-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; BKPSDM Kota Banjarmasin 2026. All rights reserved.</p>
                    <a
                        href="https://github.com/rudininh/antrian-bkpsdm"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-teal-700 transition hover:text-teal-600"
                    >
                        Open Source: github.com/rudininh/antrian-bkpsdm
                    </a>
                </div>
            </footer>
        </div>
    </div>
</template>
