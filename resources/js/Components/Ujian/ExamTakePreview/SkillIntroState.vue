<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import {
    IconArrowLeft,
    IconArrowRight,
} from "@tabler/icons-vue";

defineProps({
    currentBlock: { type: Object, required: true },
    blocks: { type: Array, default: () => [] },
    canGoPrev: { type: Boolean, default: false },
});

const emit = defineEmits(["previous", "next"]);
</script>

<template>
    <div
        class="w-full max-w-3xl overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-white shadow-standard dark:bg-surface-container"
    >
        <div class="bg-primary px-5 py-3.5 text-on-primary">
            <div class="min-w-0">
                <span
                    class="text-xs font-semibold uppercase tracking-widest opacity-70"
                    >Bagian Ujian</span
                >
                <h1 class="text-title-lg font-bold">
                    {{ currentBlock.keterampilan.nama }}
                </h1>
            </div>
        </div>
        <div class="px-5 py-4">
            <RichTextViewer
                :content="currentBlock.keterampilan.deskripsi"
                class="mb-3 text-sm leading-snug [&_.rich-text-content]:leading-snug [&_.rich-text-content_p]:mb-2 [&_.rich-text-content_h1]:mb-2 [&_.rich-text-content_h2]:mb-1.5 [&_.rich-text-content_h3]:mb-1.5 [&_.rich-text-content_ul]:mb-2 [&_.rich-text-content_ol]:mb-2"
            />
            <div class="mb-3 overflow-hidden rounded-xl border border-outline-variant/40">
                <div
                    class="flex items-center justify-between gap-3 bg-surface-container-low/50 px-3 py-2"
                >
                    <p class="text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Bagian Soal
                    </p>
                    <span
                        class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary"
                    >
                        {{
                            blocks.filter(
                                (x) =>
                                    x.keterampilan.kode === currentBlock.keterampilan.kode,
                            ).length
                        }}
                        bagian
                    </span>
                </div>
                <div class="divide-y divide-outline-variant/30">
                    <div
                        v-for="(b, i) in blocks.filter(
                            (x) =>
                                x.keterampilan.kode === currentBlock.keterampilan.kode,
                        )"
                        :key="i"
                        class="flex min-w-0 items-center gap-3 bg-surface-white px-3 py-2.5 dark:bg-surface-container"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-bold text-primary"
                        >
                            {{ i + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-text-heading">
                                {{ b.bagianSoal.nama }}
                            </p>
                            <p class="text-xs text-text-muted">
                                {{ b.jumlahSoal }} soal
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full border border-outline-variant/50 bg-surface-container-low px-2 py-1 text-xs font-semibold tabular-nums text-text-heading"
                        >
                            Soal {{ b.nomorMulai }}–{{ b.nomorMulai + b.jumlahSoal - 1 }}
                        </span>
                    </div>
                </div>
            </div>
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
                    class="col-span-full flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary shadow-md transition-all duration-150 hover:bg-primary/90 active:scale-[0.98]"
                    :class="{ '!col-span-1': canGoPrev }"
                >
                    Lanjut <IconArrowRight :size="16" />
                </button>
            </div>
        </div>
    </div>
</template>
