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
            </div>
        </div>
    </div>
</template>
