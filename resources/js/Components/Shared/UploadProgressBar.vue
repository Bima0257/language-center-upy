<script setup>
import { computed } from 'vue';
import { IconFileMusic, IconPhoto, IconFiles } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    label: { type: String, default: 'Mengunggah & mengompres audio...' },
    mediaType: { type: String, default: 'audio' },
});

const title = computed(() => {
    if (props.mediaType === 'image') return 'Memproses Gambar';
    if (props.mediaType === 'both') return 'Memproses File';
    return 'Memproses Audio';
});

const icon = computed(() => {
    if (props.mediaType === 'image') return IconPhoto;
    if (props.mediaType === 'both') return IconFiles;
    return IconFileMusic;
});
</script>

<template>
    <BaseModal
        :show="show"
        max-width="sm"
        :closeable="false"
        aria-labelledby="upload-progress-title"
        aria-describedby="upload-progress-description"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-6 py-4 text-center">
                    <h3 id="upload-progress-title" class="text-title-lg font-semibold text-text-heading">
                        {{ title }}
                    </h3>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <div class="px-6 pb-6 pt-5 text-center" role="status" aria-live="polite">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-pastel-purple/50">
                    <component :is="icon" :size="28" class="animate-pulse text-secondary" />
                </div>
                <p id="upload-progress-description" class="mb-6 text-body-md text-text-body">{{ label }}</p>
                <div class="h-2.5 overflow-hidden rounded-full bg-surface-container">
                    <div class="progress-bar-animated h-full w-1/3 rounded-full bg-primary-container"></div>
                </div>
                <p class="mt-4 text-label-md text-text-muted">
                    File besar bisa memakan waktu beberapa detik. Mohon jangan tutup halaman ini.
                </p>
            </div>
        </template>
    </BaseModal>
</template>
