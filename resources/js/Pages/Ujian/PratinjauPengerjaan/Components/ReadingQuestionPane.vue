<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";

defineProps({
    materiSoal: { type: Object, default: null },
    bagianSoalLabel: { type: String, default: "" },
    imageSrc: { type: String, default: null },
    questionText: { type: String, default: "" },
    isStandalone: { type: Boolean, default: false },
});
</script>

<template>
    <section
        class="flex-1 min-h-0 w-full border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright md:flex-none"
        :class="isStandalone ? 'md:w-1/2' : 'md:w-3/5'"
    >
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <span
                        class="bg-pastel-blue text-text-heading px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block"
                    >{{ isStandalone ? "Soal Mandiri" : bagianSoalLabel }}</span
                >
                <h1
                    v-if="materiSoal"
                    class="font-serif text-2xl leading-tight text-text-heading font-bold mb-6"
                >
                    {{ materiSoal.judul }}
                </h1>
                <h1
                    v-else-if="isStandalone && questionText"
                    class="font-headline-md text-headline-md text-text-heading leading-snug"
                >
                    {{ questionText }}
                </h1>
                <h1
                    v-else
                    class="font-serif text-2xl leading-tight text-text-heading font-bold mb-6"
                >
                    Soal Standalone
                </h1>
            </div>
            <div
                v-if="materiSoal?.isi_teks"
                class="font-serif text-[18px] leading-[1.8] text-text-body space-y-6 [&_.rich-text-content]:leading-[1.8] [&_.rich-text-content]:text-text-body [&_.rich-text-content_p]:mb-6"
            >
                <RichTextViewer :content="materiSoal.isi_teks" />
            </div>
            <div
                v-if="imageSrc"
                class="my-6 rounded-xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard"
            >
                <img :src="imageSrc" class="w-full h-auto rounded-lg" />
            </div>
        </div>
    </section>
</template>

<style scoped>
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
