<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import {
    IconArrowLeft,
    IconArrowRight,
    IconBook,
    IconCheck,
    IconHeadphones,
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
        class="bg-surface-white rounded-3xl shadow-standard border border-outline-variant/30 max-w-3xl w-full overflow-hidden"
    >
        <div
            class="bg-secondary px-8 py-7 flex items-center gap-4 text-on-secondary"
        >
            <div
                class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                :class="
                    currentBlock.materi === 'audio'
                        ? 'bg-pastel-purple'
                        : 'bg-pastel-blue'
                "
            >
                <IconHeadphones
                    v-if="currentBlock.materi === 'audio'"
                    :size="28"
                    class="text-secondary"
                />
                <IconBook v-else :size="28" class="text-secondary" />
            </div>
            <div>
                <span
                    class="text-label-md uppercase tracking-widest opacity-70 font-semibold"
                    >Bagian Ujian</span
                >
                <h1 class="text-headline-md font-bold">
                    {{ currentBlock.keterampilan.nama }}
                </h1>
            </div>
        </div>
        <div class="px-8 py-6">
            <RichTextViewer
                :content="currentBlock.keterampilan.deskripsi"
                class="text-body-md leading-relaxed mb-5"
            />
            <div class="flex flex-wrap gap-2 mb-2">
                <span
                    v-for="(b, i) in blocks.filter(
                        (x) =>
                            x.keterampilan.kode === currentBlock.keterampilan.kode,
                    )"
                    :key="i"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-surface-container-low text-label-md font-semibold text-text-heading"
                >
                    <IconCheck v-if="false" :size="13" />
                    {{ b.bagianSoal.nama }} · Soal {{ b.nomorMulai }}–{{ b.nomorMulai + b.jumlahSoal - 1 }}
                </span>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button
                    v-if="canGoPrev"
                    @click="emit('previous')"
                    class="flex items-center justify-center gap-2 px-5 py-3 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                >
                    <IconArrowLeft :size="16" /> Kembali
                </button>
                <button
                    @click="emit('next')"
                    class="col-span-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-full text-label-md font-semibold bg-secondary text-on-secondary hover:bg-secondary/90 transition-all shadow-md active:scale-[0.98] duration-150"
                    :class="{ '!col-span-1': canGoPrev }"
                >
                    Lanjut <IconArrowRight :size="16" />
                </button>
            </div>
        </div>
    </div>
</template>
