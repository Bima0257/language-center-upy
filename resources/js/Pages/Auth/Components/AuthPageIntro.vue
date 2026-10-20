<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    spacingClass: { type: String, default: 'mb-8' },
});
</script>

<template>
    <header :class="spacingClass">
        <h1 class="text-headline-md font-bold text-primary mb-2">{{ title }}</h1>
        <p class="text-text-body text-body-md">{{ description }}</p>
    </header>
</template>
