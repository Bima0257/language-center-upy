<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: { type: String, default: "neutral" },
});

const variantClasses = {
    neutral: "bg-surface-container text-text-body",
    primary: "bg-primary-container text-white",
    secondary: "bg-secondary/10 text-secondary",
    success: "bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300",
    warning: "bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300",
    danger: "bg-error-red/10 text-error-red",
    pastel: "bg-pastel-purple text-primary",
    "pastel-blue": "bg-pastel-blue text-primary",
    "pastel-peach": "bg-pastel-peach text-primary",
};

const classes = computed(() => variantClasses[props.variant]);
</script>

<template>
    <span
        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-label-md whitespace-nowrap"
        :class="classes"
    >
        <slot />
    </span>
</template>
