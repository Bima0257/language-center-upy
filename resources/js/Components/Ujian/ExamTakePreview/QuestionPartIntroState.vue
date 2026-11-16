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
            class="flex items-center justify-between gap-4 bg-primary px-5 py-3.5 text-on-primary"
        >
            <div>
                <span
                    class="text-xs font-semibold uppercase tracking-widest opacity-70"
                    >{{ currentBlock.section.nama }}</span
                >
                <h1 class="text-title-lg font-bold">
                    {{ ringkasan.nama }}
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
            <div class="grid grid-cols-2 gap-2 border-t border-outline-variant/40 pt-3">
                <button
                    v-if="canGoPrev"
                    @click="emit('previous')"
                    class="flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-surface-white px-4 py-2.5 text-sm font-medium text-text-heading transition-all hover:bg-surface-container-low"
                >
                    <IconArrowLeft :size="16" /> Kembali
                </button>
                <button
                    @click="emit('next')"
                    class="flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary shadow-md transition-all duration-150 hover:bg-primary/90 active:scale-[0.98] col-span-full"
                    :class="{ '!col-span-1': canGoPrev }"
                >
                    Mulai Mengerjakan <IconArrowRight :size="16" />
                </button>
            </div>
        </div>
    </div>
</template>
