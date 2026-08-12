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
    draft: "bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300",
    approved: "bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300",
    rejected: "bg-error-red/10 text-error-red",
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
        "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400",
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
