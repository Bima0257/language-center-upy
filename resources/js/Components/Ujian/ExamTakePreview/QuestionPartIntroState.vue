<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import { IconArrowLeft, IconArrowRight } from "@tabler/icons-vue";
import { computed } from "vue";

const props = defineProps({
    currentBlock: { type: Object, required: true },
    partBlocks: { type: Array, default: () => [] },
    canGoPrev: { type: Boolean, default: false },
});

const emit = defineEmits(["previous", "next"]);

function namaPartRingkas(namaSection, namaPart) {
    const section = String(namaSection || "");
    const part = String(namaPart || "");

    if (!section || !part) return part;

    const awalan = `${section} `;

    if (!part.toLowerCase().startsWith(awalan.toLowerCase())) return part;

    return part.slice(awalan.length).trim() || part;
}

const ringkasan = computed(() => {
    const blocks = props.partBlocks.length
        ? props.partBlocks
        : [props.currentBlock];
    const pertama = blocks[0];

    return {
        nama:
            pertama?.partSoal?.nama
            ?? props.currentBlock.partSoal?.nama
            ?? "Part Soal",
        petunjuk:
            pertama?.partSoal?.petunjuk
            ?? props.currentBlock.partSoal?.petunjuk
            ?? null,
        nomorMulai: pertama?.nomorMulai ?? props.currentBlock.nomorMulai,
        jumlahSoal: blocks.reduce(
            (total, block) => total + Number(block.jumlahSoal || 0),
            0,
        ),
    };
});
</script>

<template>
    <div
        class="w-full max-w-3xl overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-white shadow-standard dark:bg-surface-container"
    >
        <div
            class="flex items-center justify-between gap-4 bg-primary px-5 py-3.5 text-on-primary dark:bg-primary-container dark:text-on-primary-container"
        >
            <div>
                <span
                    class="text-xs font-semibold uppercase tracking-widest opacity-70"
                    >{{ currentBlock.section.nama }}</span
                >
                <h1 class="text-title-lg font-bold">
                    {{ namaPartRingkas(currentBlock.section.nama, ringkasan.nama) }}
                </h1>
            </div>
            <div class="shrink-0 text-right">
                <span
                    class="text-xs font-semibold uppercase tracking-widest opacity-70"
                    >Soal</span
                >
                <p class="text-lg font-bold">
                    {{ ringkasan.nomorMulai }}–{{ ringkasan.nomorMulai + ringkasan.jumlahSoal - 1 }}
                </p>
            </div>
        </div>
        <div class="px-5 py-4">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-text-muted">
                Petunjuk Pengerjaan
            </p>
            <RichTextViewer
                :content="ringkasan.petunjuk"
                class="mb-3 text-sm leading-snug [&_.rich-text-content]:leading-snug [&_.rich-text-content_p]:mb-2 [&_.rich-text-content_h1]:mb-2 [&_.rich-text-content_h2]:mb-1.5 [&_.rich-text-content_h3]:mb-1.5 [&_.rich-text-content_ul]:mb-2 [&_.rich-text-content_ol]:mb-2"
            />
            <div class="flex items-center justify-end gap-2 border-t border-outline-variant/40 pt-3">
                <button
                    v-if="canGoPrev"
                    @click="emit('previous')"
                    class="inline-flex items-center gap-1.5 rounded-full border border-outline-variant bg-surface-white px-3.5 py-2 text-xs font-semibold text-text-heading transition-all hover:bg-surface-container-low"
                >
                    <IconArrowLeft :size="14" /> Kembali
                </button>
                <button
                    @click="emit('next')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-accent px-3.5 py-2 text-xs font-semibold text-on-accent shadow-md transition-all duration-150 hover:bg-accent/90 active:scale-[0.98]"
                >
                    Mulai Mengerjakan <IconArrowRight :size="14" />
                </button>
            </div>
        </div>
    </div>
</template>
