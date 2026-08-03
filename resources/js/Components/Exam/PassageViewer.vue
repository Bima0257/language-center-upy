<script setup>
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';

defineProps({
    passage: { type: Object, required: true },
});
</script>

<template>
    <div>
        <h2 class="text-title-lg font-semibold text-primary mb-3">{{ passage.title || 'Materi Soal' }}</h2>
        <RichTextViewer :content="passage.content_text" class="text-body-md" />
    </div>
</template>
