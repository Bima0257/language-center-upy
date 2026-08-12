<script setup>
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';

defineProps({
    passage: { type: Object, required: true },
});
</script>

<template>
    <div>
        <h2 class="text-title-lg font-semibold text-primary mb-3">{{ passage.title || 'Materi Soal' }}</h2>
        <div v-if="passage.image_url" class="mb-4 rounded-xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard">
            <img :src="'/storage/' + passage.image_url" class="w-full h-auto rounded-lg" />
        </div>
        <RichTextViewer :content="passage.content_text" class="text-body-md" />
    </div>
</template>
