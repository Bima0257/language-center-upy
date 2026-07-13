<script setup>
defineProps({
    group: { type: Object, required: true },
});
</script>

<template>
    <div>
        <h2 class="text-title-lg font-semibold text-primary mb-3">{{ group.title || 'Reading Passage' }}</h2>
        <div class="prose prose-sm max-w-none text-body-md text-text-body leading-relaxed whitespace-pre-line">
            {{ group.passage_text }}
        </div>
    </div>
</template>
