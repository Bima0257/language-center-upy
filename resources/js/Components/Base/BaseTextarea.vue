<script setup>
import { computed } from "vue";

const props = defineProps({
    error: { type: Boolean, default: false },
});

const model = defineModel({
    type: String,
    default: "",
});

const focusClass = computed(() =>
    props.error ? "focus:border-error-red" : "focus:border-secondary",
);
</script>

<template>
    <textarea
        v-model="model"
        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        :class="focusClass"
    />
</template>
