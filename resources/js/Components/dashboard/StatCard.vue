<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    change: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <article class="rounded-[1.75rem] border border-white/70 bg-white/90 p-5 shadow-[var(--shadow-panel)] backdrop-blur">
        <p class="text-sm font-medium text-slate-500">{{ label }}</p>
        <div class="mt-4 flex items-end justify-between gap-3">
            <p class="text-3xl font-semibold text-slate-900">{{ value }}</p>
            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                {{ change }}
            </span>
        </div>
    </article>
</template>
