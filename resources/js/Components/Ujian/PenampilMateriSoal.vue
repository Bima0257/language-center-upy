<script setup>
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';
import { useMediaLoad } from '@/Composables/useMediaLoad';

const props = defineProps({
    materiSoal: { type: Object, required: true },
});

const imageLoading = useMediaLoad(() => props.materiSoal.url_gambar);
</script>

<template>
    <div>
        <h2 class="text-title-lg font-semibold text-text-heading mb-3">{{ materiSoal.judul || 'Materi Soal' }}</h2>
        <div v-if="materiSoal.url_gambar" class="mb-4 rounded-xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard">
            <BaseMediaLoader
                :loading="imageLoading.loading.value"
                media-type="image"
                skeleton-class="h-44 rounded-lg"
            >
                <img
                    :src="'/media/' + materiSoal.url_gambar"
                    class="w-full h-auto rounded-lg"
                    @load="imageLoading.onLoad()"
                    @error="imageLoading.onError()"
                />
            </BaseMediaLoader>
        </div>
        <RichTextViewer :content="materiSoal.isi_teks" class="text-body-md" />
    </div>
</template>
