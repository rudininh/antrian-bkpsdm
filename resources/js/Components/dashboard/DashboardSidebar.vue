<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const appName = computed(() => page.props.appName ?? 'Antrian BKPSDM');
const user = computed(() => page.props.auth?.user);
const permissions = computed(() => page.props.permissions ?? {});

const navItems = computed(() =>
    [
        { name: 'Dashboard', href: route('dashboard'), active: route().current('dashboard'), hint: 'Utama' },
        permissions.value.manageMasterData
            ? { name: 'Layanan', href: route('services.index'), active: route().current('services.*'), hint: 'Admin' }
            : null,
        permissions.value.manageMasterData
            ? { name: 'Loket', href: route('counters.index'), active: route().current('counters.*'), hint: 'Admin' }
            : null,
        permissions.value.manageQueues
            ? { name: 'Antrian', href: route('queues.index'), active: route().current('queues.*'), hint: 'Operasi' }
            : null,
        permissions.value.manageQueues
            ? { name: 'Monitoring', href: route('monitoring.index'), active: route().current('monitoring.*'), hint: 'Live' }
            : null,
        { name: 'Profil', href: route('profile.edit'), active: route().current('profile.*'), hint: 'Akun' },
    ].filter(Boolean),
);
</script>

<template>
    <aside class="hidden w-72 shrink-0 rounded-[2rem] border border-white/60 bg-slate-950/95 p-6 text-white shadow-2xl shadow-slate-900/15 lg:flex lg:flex-col">
        <div>
            <p class="text-xs uppercase tracking-[0.35em] text-teal-300/80">BKPSDM</p>
            <h1 class="mt-3 text-2xl font-semibold">{{ appName }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-300">
                Panel operasional untuk memantau arus layanan dan antrian harian.
            </p>
        </div>

        <nav class="mt-10 space-y-2">
            <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm transition"
                :class="item.active ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
            >
                <span>{{ item.name }}</span>
                <span class="text-xs opacity-70">{{ item.hint }}</span>
            </Link>
        </nav>

        <div class="mt-auto space-y-4">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm font-medium text-white">Status Sistem</p>
                <p class="mt-2 text-3xl font-semibold text-teal-300">Online</p>
                <p class="mt-2 text-sm text-slate-300">Semua loket siap melayani dengan sinkronisasi data real-time.</p>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Login Aktif</p>
                <p class="mt-2 text-lg font-semibold text-white">{{ user?.name }}</p>
                <p class="text-sm text-slate-300">{{ user?.email }}</p>
                <p class="mt-2 text-xs uppercase tracking-[0.25em] text-teal-300">{{ user?.role }}</p>
            </div>
        </div>
    </aside>
</template>
