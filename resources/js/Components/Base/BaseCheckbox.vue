<script setup>
const props = defineProps({
    label: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    indeterminate: { type: Boolean, default: false },
});

const model = defineModel({ type: Boolean, default: false });
</script>

<template>
    <label
        class="inline-flex items-center gap-2.5 select-none"
        :class="props.disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'"
    >
        <input
            v-model="model"
            type="checkbox"
            :disabled="props.disabled"
            :indeterminate="props.indeterminate"
            class="size-4 rounded border-outline-variant accent-secondary focus:ring-secondary"
        />
        <span v-if="props.label" class="text-body-md text-text-body">{{ props.label }}</span>
        <slot v-else />
    </label>
</template>
