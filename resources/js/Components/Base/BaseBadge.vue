<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: { type: String, default: "neutral" },
});

const variantClasses = {
    neutral: "bg-neutral-container text-on-neutral-container",
    primary: "bg-primary text-on-primary",
    secondary: "bg-secondary text-on-secondary",
    success: "bg-success text-on-success",
    warning: "bg-warning text-on-warning",
    danger: "bg-error-red text-on-error",
    pastel: "bg-pastel-purple text-text-pastel",
    "pastel-blue": "bg-pastel-blue text-text-pastel",
    "pastel-peach": "bg-pastel-peach text-text-pastel",
    peach: "bg-pastel-peach text-text-pastel",
};

const classes = computed(() => variantClasses[props.variant] ?? variantClasses.neutral);
</script>

<template>
    <span
        class="inline-flex h-6 w-fit items-center justify-center gap-1.5 rounded-full px-3 text-xs font-medium whitespace-nowrap"
        :class="classes"
    >
        <slot />
    </span>
</template>
