<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const trackClass = computed(() =>
    props.modelValue ? "bg-secondary" : "bg-track-neutral",
);
</script>

<template>
    <label
        class="inline-flex items-center gap-3 cursor-pointer select-none"
        :class="{ 'opacity-50 pointer-events-none': disabled }"
    >
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            :disabled="disabled"
            class="relative w-11 h-6 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:ring-offset-2"
            :class="trackClass"
            @click="emit('update:modelValue', !modelValue)"
        >
            <span
                class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform"
                :class="modelValue ? 'translate-x-5' : ''"
            />
        </button>
        <span v-if="label" class="text-text-body text-body-md">{{ label }}</span>
    </label>
</template>
