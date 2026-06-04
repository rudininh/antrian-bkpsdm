<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        default: () => ({}),
    },
    label: {
        type: String,
        default: 'data',
    },
});

const hasPages = computed(() => (props.pagination?.last_page ?? 1) > 1);
const from = computed(() => props.pagination?.from ?? 0);
const to = computed(() => props.pagination?.to ?? 0);
const total = computed(() => props.pagination?.total ?? 0);
const currentPage = computed(() => props.pagination?.current_page ?? 1);
const lastPage = computed(() => props.pagination?.last_page ?? 1);
</script>

<template>
    <div
        v-if="hasPages"
        class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"
    >
        <p>
            <span v-if="total > 0">
                Menampilkan {{ from }}-{{ to }} dari {{ total }} {{ label }}
            </span>
            <span v-else>
                Tidak ada {{ label }} untuk ditampilkan
            </span>
        </p>

        <div class="flex items-center gap-2">
            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                Halaman {{ currentPage }} / {{ lastPage }}
            </span>

            <Link
                v-if="pagination.prev_page_url"
                :href="pagination.prev_page_url"
                preserve-scroll
                class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
            >
                Sebelumnya
            </Link>
            <span
                v-else
                class="cursor-not-allowed rounded-full border border-slate-100 bg-white px-3 py-2 text-slate-300"
            >
                Sebelumnya
            </span>

            <Link
                v-if="pagination.next_page_url"
                :href="pagination.next_page_url"
                preserve-scroll
                class="rounded-full border border-slate-200 bg-slate-950 px-3 py-2 text-white transition hover:bg-slate-800"
            >
                Berikutnya
            </Link>
            <span
                v-else
                class="cursor-not-allowed rounded-full border border-slate-100 bg-slate-200 px-3 py-2 text-slate-400"
            >
                Berikutnya
            </span>
        </div>
    </div>
</template>
