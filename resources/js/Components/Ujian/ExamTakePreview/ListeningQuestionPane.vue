<script setup>
import AudioPlayer from "@/Components/Ujian/AudioPlayer.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";

const props = defineProps({
    materiSoal: { type: Object, default: null },
    bagianSoalLabel: { type: String, default: "" },
    imageSrc: { type: String, default: null },
    audioSrc: { type: String, default: null },
});

const imageLoading = useMediaLoad(() => props.imageSrc);
</script>

<template>
    <section
        class="preview-material-panel flex min-h-0 min-w-0 flex-col overflow-hidden rounded-[18px] border bg-surface-container-low"
    >
        <div class="min-h-0 flex-1 overflow-y-auto scroll-hide p-3 sm:p-4 lg:p-4">
            <div class="mx-auto w-full max-w-3xl">
                <div class="mb-3">
                    <span
                        class="mb-1.5 inline-flex rounded-md bg-pastel-blue px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-info"
                        >{{ bagianSoalLabel || "Audio" }}</span
                    >
                    <h1
                        class="text-lg font-bold leading-[1.2] tracking-[-0.025em] text-text-heading sm:text-xl"
                    >
                        {{ materiSoal?.judul || bagianSoalLabel || "Audio" }}
                    </h1>
                </div>
                <p class="mb-2 text-[13px] text-text-muted">
                    Putar audio sebelum menjawab soal.
                </p>
                <div
                    v-if="imageSrc"
                    class="preview-media-frame rounded-lg border bg-surface-container-lowest p-1.5"
                >
                    <div class="relative min-h-[240px] w-full sm:min-h-[320px]">
                        <BaseMediaLoader
                            :loading="imageLoading.loading.value"
                            media-type="image"
                            skeleton-class="h-[240px] rounded-md sm:h-[320px]"
                        >
                            <img
                                :src="imageSrc"
                                :alt="materiSoal?.judul || 'Gambar materi audio'"
                                class="absolute inset-0 h-full w-full rounded-md object-contain"
                                @load="imageLoading.onLoad()"
                                @error="imageLoading.onError()"
                            />
                        </BaseMediaLoader>
                        <div
                            class="pointer-events-none absolute inset-0 rounded-lg bg-scrim/5"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="preview-audio-bar shrink-0 border-t bg-surface-white">
            <AudioPlayer v-if="audioSrc" :src="audioSrc" bar compact />
            <div v-else class="px-4 py-3 text-center">
                <p class="text-sm text-text-muted">
                    Belum ada audio untuk soal ini.
                </p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.preview-material-panel {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 86%,
        transparent
    );
    background-color: var(--color-surface-white);
}

.preview-media-frame {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 60%,
        transparent
    );
}

.preview-audio-bar {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 72%,
        transparent
    );
}

.scroll-hide::-webkit-scrollbar {
    width: 6px;
}
.scroll-hide::-webkit-scrollbar-track {
    background: var(--color-scrollbar-track);
}
.scroll-hide::-webkit-scrollbar-thumb {
    background: var(--color-scrollbar-thumb);
    border-radius: 10px;
}
</style>
