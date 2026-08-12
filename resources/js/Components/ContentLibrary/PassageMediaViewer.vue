<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import { IconHeadphones, IconPhoto } from "@tabler/icons-vue";

defineProps({
    passage: { type: Object, required: true },
    expanded: { type: Boolean, default: false },
    clamp: { type: Number, default: 3 },
    variant: { type: String, default: "full" },
});

defineEmits(["toggle-expand"]);
</script>

<template>
    <!-- VARIANT FULL: tampilan passage lengkap (ContentLibrary) -->
    <div
        v-if="variant === 'full' && passage.type === 'audio' && passage.audio_url"
        class="mt-2"
    >
        <audio controls :src="'/storage/' + passage.audio_url" class="w-full max-w-md h-9"></audio>
    </div>
    <div
        v-else-if="variant === 'full' && passage.type === 'image' && passage.image_url"
        class="mt-2"
    >
        <img
            :src="'/storage/' + passage.image_url"
            class="max-h-44 rounded-xl border border-outline-variant/30 object-contain"
        />
    </div>
    <template v-else-if="variant === 'full' && passage.content_text">
        <RichTextViewer
            :content="passage.content_text"
            :clamp="expanded ? 0 : clamp"
            class="mt-2"
        />
        <button
            v-if="passage.content_text.length > 180"
            @click="$emit('toggle-expand')"
            class="mt-1 text-secondary text-label-md font-medium hover:underline"
        >
            {{ expanded ? "Sembunyikan ▲" : "Lihat Selengkapnya ▼" }}
        </button>
    </template>

    <!-- VARIANT COMPACT: tampilan ringkas (PassageView) -->
    <template v-else-if="variant === 'compact'">
        <template v-if="(passage.type === 'audio' || passage.audio_url) && passage.audio_url">
            <div class="flex items-center gap-2 mb-1">
                <IconHeadphones :size="14" class="text-text-muted" />
                <audio
                    :src="'/storage/' + passage.audio_url"
                    controls
                    class="h-8 w-full max-w-xs"
                    preload="none"
                ></audio>
            </div>
        </template>
        <template v-else-if="(passage.type === 'image' || passage.image_url) && passage.image_url">
            <div class="flex items-center gap-2 mb-1">
                <IconPhoto :size="14" class="text-text-muted" />
                <img
                    :src="'/storage/' + passage.image_url"
                    class="h-12 rounded-lg object-cover"
                    :alt="passage.title"
                />
            </div>
        </template>
        <p
            v-else-if="passage.content_text"
            class="text-label-md text-text-body mb-2"
        >
            <RichTextViewer :content="passage.content_text" :clamp="2" />
        </p>
    </template>
</template>
