<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    description: {
        type: String,
        default: 'Ringkasan operasional layanan hari ini.',
    },
    dateLabel: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <header class="rounded-[2rem] border border-[var(--color-line)] bg-white/85 px-5 py-4 shadow-[var(--shadow-panel)] backdrop-blur sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.25em] text-teal-700">Dashboard</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ title }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ description }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-teal-50 px-4 py-3 text-sm text-teal-900">
                    <div class="font-semibold">Tanggal Operasional</div>
                    <div class="text-teal-700">{{ dateLabel }}</div>
                </div>
                <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <div class="font-semibold">Operator</div>
                    <div class="text-amber-700">{{ user?.name }}</div>
                </div>
                <div class="flex items-center gap-2 rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    <Link :href="route('profile.edit')" class="font-semibold text-slate-900">Profil</Link>
                    <span>/</span>
                    <Link :href="route('logout')" method="post" as="button" class="font-semibold text-rose-600">Keluar</Link>
                </div>
            </div>
        </div>
    </header>
</template>
