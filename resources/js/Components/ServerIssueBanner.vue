<script setup>
defineProps({
    message: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['retry']);
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[100] flex justify-center px-4 pt-4 sm:px-6">
        <div class="pointer-events-auto w-full max-w-4xl rounded-[1.5rem] border border-amber-200 bg-white/95 px-4 py-4 shadow-[0_24px_70px_-50px_rgba(15,23,42,0.55)] backdrop-blur">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.32em] text-amber-700">Gangguan Sementara</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">
                        {{ message }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-slate-500">
                        Halaman ini tetap terbuka. Tidak ada refresh otomatis saat server belum pulih.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                    @click="emit('retry')"
                >
                    Coba Lagi
                </button>
            </div>
        </div>
    </div>
</template>
