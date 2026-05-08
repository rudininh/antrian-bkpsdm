<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { appRoute } from '@/utils/route';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { loginEnabled, canResetPassword, status } = defineProps({
    loginEnabled: {
        type: Boolean,
        default: true,
    },
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    if (!loginEnabled) {
        return;
    }

    form.post(appRoute('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="mb-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-700">LOGIN ANTRIAN BKPSDM</p>
            <p class="mt-2 text-sm text-slate-500">Masuk untuk mengelola layanan antrian dan pengaturan sistem.</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div v-if="!loginEnabled" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Fitur login sementara dinonaktifkan. Akses internal akan memakai auto-login pada host trusted.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    :disabled="!loginEnabled"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    :disabled="!loginEnabled"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" :disabled="!loginEnabled" />
                    <span class="ms-2 text-sm text-gray-600"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword && loginEnabled"
                    :href="appRoute('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing || !loginEnabled }"
                    :disabled="form.processing || !loginEnabled"
                >
                    {{ loginEnabled ? 'Log in' : 'Dinonaktifkan' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
