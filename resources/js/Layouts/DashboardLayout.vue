<script setup>
import DashboardHeader from '@/Components/dashboard/DashboardHeader.vue';
import DashboardSidebar from '@/Components/dashboard/DashboardSidebar.vue';
</script>

<template>
    <div class="min-h-screen">
        <div class="mx-auto flex min-h-screen max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <DashboardSidebar />

            <div class="flex-1">
                <DashboardHeader :title="title" :description="description" :date-label="dateLabel" />

                <main class="mt-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
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
    },
};
</script>
