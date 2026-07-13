<script setup>
defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <h3 class="text-title-lg font-semibold text-primary mb-2">{{ section.title }}</h3>
        <p class="text-text-muted text-body-md">Reading — {{ section.total_questions }} soal</p>
        <div v-if="section.question_groups?.length" class="mt-4 space-y-3">
            <div v-for="group in section.question_groups" :key="group.id"
                 class="p-4 bg-surface-container-low rounded-2xl">
                <p class="font-medium text-primary">{{ group.title || 'Passage' }}</p>
                <p class="text-text-muted text-label-md">{{ group.questions?.length || 0 }} soal</p>
            </div>
        </div>
    </div>
</template>
