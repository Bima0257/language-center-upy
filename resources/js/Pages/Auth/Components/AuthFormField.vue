<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: undefined },
});
</script>

<template>
    <div class="space-y-2">
        <BaseFieldLabel :for="id" :value="label" />
        <slot />
        <BaseInputError class="mt-2" :message="error" />
    </div>
</template>
