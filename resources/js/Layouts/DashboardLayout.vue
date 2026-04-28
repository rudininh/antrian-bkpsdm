<script setup>
import { appRoute } from '@/utils/route';
import DashboardHeader from '@/Components/dashboard/DashboardHeader.vue';
import DashboardSidebar from '@/Components/dashboard/DashboardSidebar.vue';
import QueueAlertBanner from '@/Components/dashboard/QueueAlertBanner.vue';
import { queueAlertMuted, toggleQueueAlertMute, useQueueAlertVoice } from '@/composables/useQueueAlertVoice';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    dateLabel: {
        type: String,
        default: '',
    },
});

const page = usePage();
const route = appRoute;
const meta = computed(() => page.props.meta ?? {});
const queueAlert = computed(() => page.props.queueAlert ?? {});
const queueAlertCount = computed(() => Number(queueAlert.value.waitingCount ?? 0));
const permissions = computed(() => page.props.permissions ?? {});
const resolvedTitle = computed(() => props.title || meta.value.title || 'Dashboard');
const resolvedDescription = computed(
    () => props.description || meta.value.description || 'Ringkasan operasional layanan hari ini.',
);
const resolvedDateLabel = computed(() => props.dateLabel || meta.value.dateLabel || '');
let queueAlertTimer = null;
useQueueAlertVoice(queueAlertCount);

onMounted(() => {
    queueAlertTimer = window.setInterval(() => {
        router.reload({
            only: ['queueAlert'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 5000);
});

onBeforeUnmount(() => {
    if (queueAlertTimer) {
        window.clearInterval(queueAlertTimer);
    }
});

const mobileNavItems = computed(() =>
    [
        { name: 'Dashboard', href: route('dashboard'), active: route().current('dashboard') },
        permissions.value.manageMasterData
            ? { name: 'Layanan', href: route('services.index'), active: route().current('services.*') }
            : null,
        permissions.value.manageQueues
            ? { name: 'Antrian', href: route('queues.index'), active: route().current('queues.*') }
            : null,
        permissions.value.manageQueues
            ? { name: 'Panggilan', href: route('monitoring.index'), active: route().current('monitoring.*') }
            : null,
        permissions.value.manageSystem
            ? { name: 'Pengaturan', href: route('system.update.index'), active: route().current('system.update.*') }
            : null,
        permissions.value.manageReports
            ? { name: 'Laporan', href: route('reports.index'), active: route().current('reports.*') }
            : null,
        { name: 'Profil', href: route('profile.edit'), active: route().current('profile.*') },
    ].filter(Boolean),
);
</script>

<template>
    <div class="min-h-screen">
        <div class="mx-auto flex min-h-screen max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <DashboardSidebar />

            <div class="min-w-0 flex-1">
                <DashboardHeader
                    :title="resolvedTitle"
                    :description="resolvedDescription"
                    :date-label="resolvedDateLabel"
                    :queue-alert-muted="queueAlertMuted"
                    @toggle-queue-alert-mute="toggleQueueAlertMute"
                />

                <QueueAlertBanner
                    :queue-alert="queueAlert"
                    :muted="queueAlertMuted"
                    @toggle-mute="toggleQueueAlertMute"
                />

                <nav class="mt-4 flex gap-3 overflow-x-auto pb-2 lg:hidden">
                    <Link
                        v-for="item in mobileNavItems"
                        :key="item.name"
                        :href="item.href"
                        class="shrink-0 rounded-2xl px-4 py-2 text-sm font-semibold transition"
                        :class="item.active ? 'bg-slate-950 text-white' : 'border border-white/70 bg-white/85 text-slate-700 shadow-[var(--shadow-panel)]'"
                    >
                        {{ item.name }}
                    </Link>
                </nav>

                <main class="mt-6 min-w-0">
                    <slot />
                </main>

                <footer class="mt-8 rounded-[1.5rem] border border-white/70 bg-white/85 px-5 py-4 text-sm text-slate-600 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.45)] sm:px-6">
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
    </div>
</template>
