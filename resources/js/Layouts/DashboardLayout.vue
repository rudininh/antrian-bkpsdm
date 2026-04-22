<script setup>
import DashboardHeader from '@/Components/dashboard/DashboardHeader.vue';
import DashboardSidebar from '@/Components/dashboard/DashboardSidebar.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
const meta = computed(() => page.props.meta ?? {});
const resolvedTitle = computed(() => props.title || meta.value.title || 'Dashboard');
const resolvedDescription = computed(
    () => props.description || meta.value.description || 'Ringkasan operasional layanan hari ini.',
);
const resolvedDateLabel = computed(() => props.dateLabel || meta.value.dateLabel || '');
</script>

<template>
    <div class="min-h-screen">
        <div class="mx-auto flex min-h-screen max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <DashboardSidebar />

            <div class="flex-1">
                <DashboardHeader
                    :title="resolvedTitle"
                    :description="resolvedDescription"
                    :date-label="resolvedDateLabel"
                />

                <main class="mt-6">
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
