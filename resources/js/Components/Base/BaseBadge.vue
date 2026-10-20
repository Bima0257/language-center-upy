<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: { type: String, default: "neutral" },
});

const variantClasses = {
    neutral: "bg-neutral-container text-on-neutral-container",
    primary: "bg-primary-container text-on-primary-container",
    secondary: "bg-secondary/10 text-secondary",
    success: "bg-success-container text-on-success-container",
    warning: "bg-warning-container text-on-warning-container",
    danger: "bg-error-container text-on-error-container",
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
