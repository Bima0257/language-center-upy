<script setup>
defineProps({
    for: {
        type: String,
        default: undefined,
    },
    value: {
        type: String,
        default: undefined,
    },
});
</script>

<template>
    <label :for="$props.for" class="block text-label-md font-medium text-primary mb-1.5">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
