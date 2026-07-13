<script setup>
defineProps({
    value: { type: [String, Number], required: true },
    label: { type: String, required: true },
});
</script>

<template>
    <div class="bg-white p-6 rounded-2xl shadow-soft border border-surface-container-low">
        <p class="text-headline-md font-bold text-primary mb-1">{{ value }}</p>
        <p class="text-label-md font-medium text-text-muted">{{ label }}</p>
    </div>
</template>
