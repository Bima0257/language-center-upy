<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    href: { type: String, default: null },
    variant: { type: String, default: "primary" },
    type: { type: String, default: "button" },
    disabled: { type: Boolean, default: false },
    size: { type: String, default: "md" },
});

const variantClasses = {
    primary: "bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary",
    secondary:
        "border border-outline-variant bg-surface-white text-text-body hover:bg-surface-container-low",
    danger: "bg-error-red text-on-error hover:bg-error-container hover:text-on-error-container",
    success: "bg-success text-on-success hover:bg-success-container hover:text-on-success-container",
    warning: "bg-warning text-on-warning hover:bg-warning-container hover:text-on-warning-container",
    ghost: "text-primary hover:bg-surface-container-low",
};

const sizeClasses = {
    xl: "py-3.5 text-title-lg font-semibold",
    lg: "px-8 py-3.5 text-label-md font-medium",
    md: "px-6 py-3 text-label-md font-medium",
    sm: "px-4 py-2 text-label-md",
    xs: "px-5 py-2.5 text-label-md font-medium",
};

const classes = computed(() =>
    [
        "inline-flex items-center justify-center gap-2 rounded-full transition-all active:scale-95 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:ring-offset-2",
        variantClasses[props.variant],
        sizeClasses[props.size],
    ].join(" "),
);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
