<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: DashboardLayout,
});

const props = defineProps({
    services: Array,
    meta: Object,
});

const page = usePage();
const editingId = ref(null);
const flashMessage = computed(() => page.props.flash?.success);

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

const submit = () => {
    if (editingId.value) {
        form.put(route('services.update', editingId.value), {
            preserveScroll: true,
            onSuccess: resetForm,
        });
        return;
    }

    form.post(route('services.store'), {
        preserveScroll: true,
        onSuccess: resetForm,
    });
};

const editItem = (service) => {
    editingId.value = service.id;
    form.name = service.name;
    form.code = service.code;
    form.description = service.description ?? '';
    form.is_active = service.is_active;
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
};

const destroyItem = (service) => {
    if (!window.confirm(`Hapus layanan ${service.name}?`)) {
        return;
    }

    form.delete(route('services.destroy', service.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Layanan" />

    <div class="space-y-6">
        <div
            v-if="flashMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ flashMessage }}
        </div>

        <section class="grid gap-6 xl:grid-cols-[0.95fr_1.35fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ editingId ? 'Edit Layanan' : 'Tambah Layanan' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">Kelola kode, nama, dan status layanan.</p>
                    </div>
                    <button
                        v-if="editingId"
                        type="button"
                        class="text-sm font-medium text-slate-500 hover:text-slate-900"
                        @click="resetForm"
                    >
                        Batal
                    </button>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div>
                        <InputLabel for="service-name" value="Nama layanan" />
                        <TextInput id="service-name" v-model="form.name" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="service-code" value="Kode layanan" />
                        <TextInput id="service-code" v-model="form.code" class="mt-1 block w-full uppercase" />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="service-description" value="Deskripsi" />
                        <textarea
                            id="service-description"
                            v-model="form.description"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                            rows="4"
                        />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <Checkbox name="service-active" v-model:checked="form.is_active" />
                        <span class="text-sm text-slate-700">Layanan aktif dan dapat dipilih saat input antrian.</span>
                    </label>

                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                        {{ editingId ? 'Simpan Perubahan' : 'Simpan Layanan' }}
                    </PrimaryButton>
                </form>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Daftar Layanan</h3>
                        <p class="mt-1 text-sm text-slate-500">Total {{ props.services.length }} layanan tersedia.</p>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Layanan</th>
                                <th class="px-5 py-4">Kode</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4">Antrian</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            <tr v-for="service in props.services" :key="service.id">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">{{ service.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ service.description || 'Tanpa deskripsi' }}</p>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ service.code }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="service.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ service.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">{{ service.queues_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-3">
                                        <button type="button" class="font-medium text-teal-700 hover:text-teal-900" @click="editItem(service)">
                                            Edit
                                        </button>
                                        <button type="button" class="font-medium text-rose-600 hover:text-rose-800" @click="destroyItem(service)">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</template>
