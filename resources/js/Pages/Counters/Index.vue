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
    counters: Array,
    meta: Object,
});

const page = usePage();
const editingId = ref(null);
const flashMessage = computed(() => page.props.flash?.success);

const form = useForm({
    name: '',
    code: '',
    location: '',
    is_active: true,
});

const submit = () => {
    if (editingId.value) {
        form.put(route('counters.update', editingId.value), {
            preserveScroll: true,
            onSuccess: resetForm,
        });
        return;
    }

    form.post(route('counters.store'), {
        preserveScroll: true,
        onSuccess: resetForm,
    });
};

const editItem = (counter) => {
    editingId.value = counter.id;
    form.name = counter.name;
    form.code = counter.code;
    form.location = counter.location ?? '';
    form.is_active = counter.is_active;
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
};

const destroyItem = (counter) => {
    if (!window.confirm(`Hapus loket ${counter.name}?`)) {
        return;
    }

    form.delete(route('counters.destroy', counter.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Loket" />

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
                            {{ editingId ? 'Edit Loket' : 'Tambah Loket' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">Atur identitas loket dan titik layanannya.</p>
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
                        <InputLabel for="counter-name" value="Nama loket" />
                        <TextInput id="counter-name" v-model="form.name" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="counter-code" value="Kode loket" />
                        <TextInput id="counter-code" v-model="form.code" class="mt-1 block w-full uppercase" />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="counter-location" value="Lokasi" />
                        <TextInput id="counter-location" v-model="form.location" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.location" />
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <Checkbox name="counter-active" v-model:checked="form.is_active" />
                        <span class="text-sm text-slate-700">Loket aktif dan dapat dipilih untuk panggilan antrian.</span>
                    </label>

                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                        {{ editingId ? 'Simpan Perubahan' : 'Simpan Loket' }}
                    </PrimaryButton>
                </form>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Daftar Loket</h3>
                    <p class="mt-1 text-sm text-slate-500">Total {{ props.counters.length }} loket terdaftar.</p>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Loket</th>
                                <th class="px-5 py-4">Kode</th>
                                <th class="px-5 py-4">Lokasi</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            <tr v-for="counter in props.counters" :key="counter.id">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ counter.name }}</td>
                                <td class="px-5 py-4">{{ counter.code }}</td>
                                <td class="px-5 py-4">{{ counter.location || '-' }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="counter.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ counter.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-3">
                                        <button type="button" class="font-medium text-teal-700 hover:text-teal-900" @click="editItem(counter)">
                                            Edit
                                        </button>
                                        <button type="button" class="font-medium text-rose-600 hover:text-rose-800" @click="destroyItem(counter)">
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
