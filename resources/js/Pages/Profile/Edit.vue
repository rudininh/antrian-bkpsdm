<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: DashboardLayout,
});

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
            <UpdateProfileInformationForm
                :must-verify-email="mustVerifyEmail"
                :status="status"
                class="max-w-xl"
            />
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
            <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
            <DeleteUserForm class="max-w-xl" />
        </div>
    </div>
</template>
