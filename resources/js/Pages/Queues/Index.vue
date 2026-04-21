<script setup>
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
    queues: Array,
    services: Array,
    statusOptions: Array,
    meta: Object,
});

const page = usePage();
const editingId = ref(null);
const flashMessage = computed(() => page.props.flash?.success);

const form = useForm({
    service_id: '',
    ticket_number: '',
    status: 'waiting',
    queued_at: new Date().toISOString().slice(0, 16),
    notes: '',
});

const submit = () => {
    if (editingId.value) {
        form.put(route('queues.update', editingId.value), {
            preserveScroll: true,
            onSuccess: resetForm,
        });
        return;
    }

    form.post(route('queues.store'), {
        preserveScroll: true,
        onSuccess: resetForm,
    });
};

const editItem = (queue) => {
    editingId.value = queue.id;
    form.service_id = queue.service_id;
    form.ticket_number = queue.ticket_number;
    form.status = queue.status;
    form.queued_at = queue.queued_at;
    form.notes = queue.notes ?? '';
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.status = 'waiting';
    form.queued_at = new Date().toISOString().slice(0, 16);
    form.clearErrors();
};

const destroyItem = (queue) => {
    if (!window.confirm(`Hapus antrian ${queue.ticket_number}?`)) {
        return;
    }

    form.delete(route('queues.destroy', queue.id), {
        preserveScroll: true,
    });
};

const statusBadge = (status) => ({
    waiting: 'bg-amber-100 text-amber-700',
    called: 'bg-emerald-100 text-emerald-700',
    serving: 'bg-sky-100 text-sky-700',
    completed: 'bg-slate-200 text-slate-700',
    skipped: 'bg-rose-100 text-rose-700',
    cancelled: 'bg-slate-100 text-slate-500',
}[status] ?? 'bg-slate-100 text-slate-700');
</script>

<template>
    <Head title="Antrian" />

    <div class="space-y-6">
        <div
            v-if="flashMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ flashMessage }}
        </div>

        <section class="grid gap-6 xl:grid-cols-[1fr_1.3fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ editingId ? 'Edit Antrian' : 'Tambah Antrian' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">Nomor tiket bisa dikosongkan agar dibuat otomatis.</p>
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
                        <InputLabel for="queue-service" value="Layanan" />
                        <select
                            id="queue-service"
                            v-model="form.service_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            <option value="">Pilih layanan</option>
                            <option v-for="service in props.services" :key="service.id" :value="service.id">
                                {{ service.name }} ({{ service.code }})
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.service_id" />
                    </div>

                    <div>
                        <InputLabel for="queue-ticket" value="Nomor tiket" />
                        <TextInput id="queue-ticket" v-model="form.ticket_number" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.ticket_number" />
                    </div>

                    <div>
                        <InputLabel for="queue-status" value="Status" />
                        <select
                            id="queue-status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            <option v-for="status in props.statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>

                    <div>
                        <InputLabel for="queue-time" value="Waktu antri" />
                        <TextInput id="queue-time" v-model="form.queued_at" type="datetime-local" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.queued_at" />
                    </div>

                    <div>
                        <InputLabel for="queue-notes" value="Catatan" />
                        <textarea
                            id="queue-notes"
                            v-model="form.notes"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                            rows="4"
                        />
                        <InputError class="mt-2" :message="form.errors.notes" />
                    </div>

                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                        {{ editingId ? 'Simpan Perubahan' : 'Simpan Antrian' }}
                    </PrimaryButton>
                </form>
            </article>

            <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[var(--shadow-panel)]">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Antrian Hari Ini</h3>
                    <p class="mt-1 text-sm text-slate-500">Data operasional yang dapat langsung diedit operator.</p>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Nomor</th>
                                <th class="px-5 py-4">Layanan</th>
                                <th class="px-5 py-4">Petugas</th>
                                <th class="px-5 py-4">Jam</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-600">
                            <tr v-for="queue in props.queues" :key="queue.id">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ queue.ticket_number }}</td>
                                <td class="px-5 py-4">{{ queue.service_name }}</td>
                                <td class="px-5 py-4">{{ queue.counter_name || (queue.status === 'waiting' ? 'Belum dipanggil' : 'Receptionist') }}</td>
                                <td class="px-5 py-4">{{ queue.queued_label }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadge(queue.status)">
                                        {{ queue.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-3">
                                        <button type="button" class="font-medium text-teal-700 hover:text-teal-900" @click="editItem(queue)">
                                            Edit
                                        </button>
                                        <button type="button" class="font-medium text-rose-600 hover:text-rose-800" @click="destroyItem(queue)">
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
