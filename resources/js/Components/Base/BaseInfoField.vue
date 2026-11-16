<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
    accent: { type: Boolean, default: false },
});
</script>

<template>
    <div class="rounded-2xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3">
        <p class="text-label-md text-text-muted">{{ label }}</p>
        <p
            class="mt-1 text-title-lg font-semibold"
            :class="accent ? 'text-secondary' : 'text-text-heading'"
        >
            {{ value }}
        </p>
    </div>
</template>
