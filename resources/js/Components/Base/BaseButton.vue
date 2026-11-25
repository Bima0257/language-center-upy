<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    href: { type: String, default: null },
    variant: { type: String, default: "primary" },
    type: { type: String, default: "button" },
    form: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    size: { type: String, default: "md" },
    iconOnly: { type: Boolean, default: false },
});

const variantClasses = {
    primary: "bg-accent text-on-accent hover:bg-accent/90",
    secondary:
        "border border-outline-variant bg-surface-white text-text-body hover:bg-surface-container-low",
    danger: "bg-error-red text-on-error hover:bg-error-container hover:text-on-error-container",
    success: "bg-success text-on-success hover:bg-success-container hover:text-on-success-container",
    warning: "bg-warning text-on-warning hover:bg-warning-container hover:text-on-warning-container",
    ghost: "text-primary hover:bg-surface-container-low",
};

const sizeClasses = {
    xl: "py-3.5 text-title-lg font-bold",
    lg: "px-8 py-3.5 text-label-md font-semibold",
    md: "px-6 py-3 text-label-md font-semibold",
    sm: "px-4 py-2 text-label-md font-semibold",
    xs: "px-5 py-2.5 text-label-md font-semibold",
};

const classes = computed(() =>
    [
        "inline-flex items-center justify-center gap-2 rounded-full transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:ring-offset-2",
        variantClasses[props.variant],
        props.iconOnly ? "h-8 w-8 p-2 gap-0" : sizeClasses[props.size],
    ].join(" "),
);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :form="form" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
