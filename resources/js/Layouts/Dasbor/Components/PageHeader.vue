<script setup>
defineProps({
    title: { type: String, default: "" },
    subtitle: { type: String, default: "" },
});
</script>

<template>
    <BaseCard class="mb-6">
        <h1 class="text-headline-md font-bold text-text-heading">{{ title }}</h1>
        <p v-if="subtitle" class="text-body-md text-text-muted mt-1">{{ subtitle }}</p>
    </BaseCard>
</template>
