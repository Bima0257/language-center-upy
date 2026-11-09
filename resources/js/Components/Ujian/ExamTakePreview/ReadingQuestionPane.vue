<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";

const props = defineProps({
    materiSoal: { type: Object, default: null },
    bagianSoalLabel: { type: String, default: "" },
    imageSrc: { type: String, default: null },
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
                    >{{ bagianSoalLabel || "Materi" }}</span
                    >
                    <h1
                        class="text-lg font-bold leading-[1.2] tracking-[-0.025em] text-text-heading sm:text-xl"
                    >
                        {{ materiSoal?.judul || "Materi Soal" }}
                    </h1>
                </div>
                <div
                    v-if="materiSoal?.isi_teks"
                    class="space-y-3 font-sans text-[13px] leading-[1.45] text-text-body sm:text-sm [&_.rich-text-content]:leading-[1.45] [&_.rich-text-content]:text-text-body [&_.rich-text-content_p]:mb-3 [&_.rich-text-content_h1]:text-lg [&_.rich-text-content_h2]:text-base [&_.rich-text-content_h3]:text-sm [&_.rich-text-content_ol]:mb-3 [&_.rich-text-content_ul]:mb-3"
                >
                    <RichTextViewer :content="materiSoal.isi_teks" />
                </div>
                <div
                    v-if="imageSrc"
                    class="preview-media-frame mt-3 rounded-lg border bg-surface-container-lowest p-1.5"
                >
                    <BaseMediaLoader
                        :loading="imageLoading.loading.value"
                        media-type="image"
                        skeleton-class="h-64 rounded-md sm:h-80"
                    >
                        <img
                            :src="imageSrc"
                            :alt="materiSoal?.judul || 'Gambar materi soal'"
                            class="h-auto max-h-[360px] w-full rounded-md object-contain"
                            @load="imageLoading.onLoad()"
                            @error="imageLoading.onError()"
                        />
                    </BaseMediaLoader>
                </div>
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
