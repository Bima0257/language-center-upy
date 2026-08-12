<script setup>
import { computed } from "vue";
import { IconFileMusic } from "@tabler/icons-vue";

const props = defineProps({
    loading: { type: Boolean, default: true },
    mediaType: { type: String, default: "image" },
    skeletonClass: { type: String, default: "" },
});

const imageSkeletonClass = computed(
    () => props.skeletonClass || "h-44 rounded-xl",
);
const audioSkeletonClass = computed(
    () => props.skeletonClass || "h-12",
);
</script>

<template>
    <div>
        <!-- Media SELALU dirender agar @load/@error tetap terpanggil -->
        <div v-show="!loading">
            <slot />
        </div>
        <!-- Skeleton tampil hanya saat loading -->
        <div v-if="loading" class="w-full">
            <!-- SKELETON GAMBAR -->
            <div
                v-if="mediaType === 'image'"
                class="w-full rounded-xl bg-surface-container animate-pulse"
                :class="imageSkeletonClass"
            />
            <!-- SKELETON AUDIO -->
            <div
                v-else
                class="flex items-center gap-3 px-4 rounded-xl bg-surface-container animate-pulse"
                :class="audioSkeletonClass"
            >
                <IconFileMusic :size="20" class="text-text-muted shrink-0" />
                <div class="flex-1 h-1.5 rounded-full bg-track-neutral overflow-hidden">
                    <div class="h-full w-2/3 rounded-full bg-primary progress-bar-animated"></div>
                </div>
            </div>
        </div>
    </div>
</template>
