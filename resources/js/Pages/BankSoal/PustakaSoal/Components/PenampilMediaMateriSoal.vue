<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";
import { IconHeadphones, IconPhoto } from "@tabler/icons-vue";

const props = defineProps({
    materiSoal: { type: Object, required: true },
    expanded: { type: Boolean, default: false },
    clamp: { type: Number, default: 3 },
    variant: { type: String, default: "full" },
});

defineEmits(["toggle-expand"]);

const audioLoading = useMediaLoad(() => props.materiSoal.url_audio);
const imageLoading = useMediaLoad(() => props.materiSoal.url_gambar);
</script>

<template>
    <!-- VARIANT FULL: tampilan materiSoal lengkap (PustakaSoal) -->
    <template v-if="variant === 'full'">
        <div v-if="materiSoal.url_audio" class="mt-2">
            <BaseMediaLoader
                :loading="audioLoading.loading.value"
                media-type="audio"
                skeleton-class="h-10 max-w-md"
            >
                <audio
                    controls
                    :src="'/media/' + materiSoal.url_audio"
                    class="w-full max-w-md h-9"
                    @load="audioLoading.onLoad()"
                    @error="audioLoading.onError()"
                ></audio>
            </BaseMediaLoader>
        </div>
        <div v-if="materiSoal.url_gambar" class="mt-2">
            <BaseMediaLoader
                :loading="imageLoading.loading.value"
                media-type="image"
                skeleton-class="h-44 rounded-xl"
            >
                <img
                    :src="'/media/' + materiSoal.url_gambar"
                    class="max-h-44 rounded-xl border border-outline-variant/30 object-contain"
                    @load="imageLoading.onLoad()"
                    @error="imageLoading.onError()"
                />
            </BaseMediaLoader>
        </div>
        <template v-if="materiSoal.isi_teks">
            <RichTextViewer
                :content="materiSoal.isi_teks"
                :clamp="expanded ? 0 : clamp"
                class="mt-2"
            />
            <button
                v-if="materiSoal.isi_teks.length > 180"
                @click="$emit('toggle-expand')"
                class="mt-1 text-secondary text-label-md font-medium hover:underline"
            >
                {{ expanded ? "Sembunyikan ▲" : "Lihat Selengkapnya ▼" }}
            </button>
        </template>
    </template>

    <!-- VARIANT COMPACT: tampilan ringkas (inline Bank Soal) -->
    <template v-else-if="variant === 'compact'">
        <template v-if="materiSoal.url_audio">
            <div class="flex items-center gap-2 mb-1">
                <IconHeadphones :size="14" class="text-text-muted" />
                <BaseMediaLoader
                    :loading="audioLoading.loading.value"
                    media-type="audio"
                    skeleton-class="h-8 max-w-xs"
                >
                    <audio
                        :src="'/media/' + materiSoal.url_audio"
                        controls
                        class="h-8 w-full max-w-xs"
                        preload="none"
                        @load="audioLoading.onLoad()"
                        @error="audioLoading.onError()"
                    ></audio>
                </BaseMediaLoader>
            </div>
        </template>
        <template v-else-if="materiSoal.url_gambar">
            <div class="flex items-center gap-2 mb-1">
                <IconPhoto :size="14" class="text-text-muted" />
                <BaseMediaLoader
                    :loading="imageLoading.loading.value"
                    media-type="image"
                    skeleton-class="h-12 rounded-lg"
                >
                    <img
                        :src="'/media/' + materiSoal.url_gambar"
                        class="h-12 rounded-lg object-cover"
                        :alt="materiSoal.judul"
                        @load="imageLoading.onLoad()"
                        @error="imageLoading.onError()"
                    />
                </BaseMediaLoader>
            </div>
        </template>
        <p
            v-else-if="materiSoal.isi_teks"
            class="text-label-md text-text-body mb-2"
        >
            <RichTextViewer :content="materiSoal.isi_teks" :clamp="2" />
        </p>
    </template>
</template>
