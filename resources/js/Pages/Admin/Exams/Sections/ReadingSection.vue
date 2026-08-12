<script setup>
defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <BaseCard>
        <h3 class="text-title-lg font-semibold text-primary mb-2">{{ section.title }}</h3>
        <p class="text-text-muted text-body-md">Reading — {{ section.total_questions }} soal</p>
    </BaseCard>
</template>
