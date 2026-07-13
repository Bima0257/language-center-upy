<script setup>
defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <h3 class="text-title-lg font-semibold text-primary mb-2">{{ section.title }}</h3>
        <p class="text-text-muted text-body-md">Listening — {{ section.total_questions }} soal</p>
    </div>
</template>
