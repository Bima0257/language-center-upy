<script setup>
import { computed } from "vue";

const props = defineProps({
    status: { type: String, default: "" },
    labels: { type: Object, default: null },
    colors: { type: Object, default: null },
});

const defaultLabels = {
    draft: "Draf",
    approved: "Disetujui",
    rejected: "Ditolak",
};
const defaultColors = {
    draft: "bg-warning-container text-on-warning-container",
    approved: "bg-success-container text-on-success-container",
    rejected: "bg-error-container text-on-error-container",
};

const label = computed(
    () =>
        props.labels?.[props.status] ??
        defaultLabels[props.status] ??
        props.status,
);
const colorClass = computed(
    () =>
        props.colors?.[props.status] ??
        defaultColors[props.status] ??
        "bg-neutral-container text-on-neutral-container",
);
</script>

<template>
    <span
        class="inline-block px-2.5 py-0.5 rounded-full text-label-md"
        :class="colorClass"
    >
        {{ label }}
    </span>
</template>
