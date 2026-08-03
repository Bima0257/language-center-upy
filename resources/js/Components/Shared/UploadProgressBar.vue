<script setup>
import { IconFileMusic } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    label: { type: String, default: 'Mengunggah & mengompres audio...' },
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[300] bg-black/50 flex items-center justify-center p-6">
            <div class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-sm w-full text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-pastel-purple/50 flex items-center justify-center">
                    <IconFileMusic :size="28" class="text-secondary animate-pulse" />
                </div>
                <h3 class="text-title-lg font-semibold text-primary mb-1">Memproses Audio</h3>
                <p class="text-text-body text-body-md mb-6">{{ label }}</p>
                <div class="h-2.5 rounded-full bg-surface-container overflow-hidden">
                    <div class="h-full w-1/3 rounded-full bg-primary-container progress-bar-animated"></div>
                </div>
                <p class="text-text-muted text-label-md mt-4">File besar bisa memakan waktu beberapa detik. Mohon jangan tutup halaman ini.</p>
            </div>
        </div>
    </Teleport>
</template>
