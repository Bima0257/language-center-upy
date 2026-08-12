<script setup>
import { IconFileMusic } from "@tabler/icons-vue";

defineProps({
    loading: { type: Boolean, default: true },
    mediaType: { type: String, default: "image" },
    skeletonClass: { type: String, default: "" },
});
</script>

<template>
    <div v-if="loading" class="w-full">
        <!-- SKELETON GAMBAR -->
        <div
            v-if="mediaType === 'image'"
            class="w-full h-44 rounded-xl bg-surface-container animate-pulse"
            :class="skeletonClass"
        />
        <!-- SKELETON AUDIO -->
        <div
            v-else
            class="flex items-center gap-3 h-12 px-4 rounded-xl bg-surface-container animate-pulse"
            :class="skeletonClass"
        >
            <IconFileMusic :size="20" class="text-text-muted shrink-0" />
            <div class="flex-1 h-1.5 rounded-full bg-track-neutral overflow-hidden">
                <div class="h-full w-2/3 rounded-full bg-primary progress-bar-animated"></div>
            </div>
        </div>
    </div>
    <slot v-else />
</template>
