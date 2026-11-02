<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: "" },
    onLabel: { type: String, default: "Aktif" },
    offLabel: { type: String, default: "Nonaktif" },
    ariaLabel: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const trackClass = computed(() =>
    props.modelValue
        ? "bg-secondary text-on-secondary"
        : "bg-track-neutral text-text-body",
);

const stateLabel = computed(() =>
    props.modelValue ? props.onLabel : props.offLabel,
);

const accessibleLabel = computed(
    () => props.ariaLabel || props.label || "Status",
);
</script>

<template>
    <label
        class="inline-flex flex-col items-start gap-1.5 select-none"
        :class="disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
    >
        <span v-if="label" class="text-text-body text-body-md">{{ label }}</span>
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            :aria-label="accessibleLabel"
            :disabled="disabled"
            class="relative inline-flex h-8 w-[5.25rem] items-center rounded-full px-1 transition-colors focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:ring-offset-2"
            :class="trackClass"
            @click="emit('update:modelValue', !modelValue)"
        >
            <span
                class="pointer-events-none absolute inset-y-0 flex items-center text-[0.625rem] font-semibold leading-none transition-all duration-200"
                :class="modelValue ? 'right-8' : 'left-8'"
            >
                {{ stateLabel }}
            </span>
            <span
                class="pointer-events-none absolute left-1 top-1 z-10 h-6 w-6 rounded-full bg-surface-white shadow-sm transition-transform duration-200"
                :class="modelValue ? 'translate-x-[3.25rem]' : 'translate-x-0'"
            />
        </button>
    </label>
</template>
