<script setup>
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import { IconArrowLeft, IconArrowRight } from "@tabler/icons-vue";

defineProps({
    currentBlock: { type: Object, required: true },
    canGoPrev: { type: Boolean, default: false },
});

const emit = defineEmits(["previous", "next"]);
</script>

<template>
    <div
        class="bg-surface-white rounded-3xl shadow-standard border border-outline-variant/30 max-w-3xl w-full overflow-hidden"
    >
        <div
            class="bg-secondary px-8 py-7 text-on-secondary flex items-center justify-between"
        >
            <div>
                <span
                    class="text-label-md uppercase tracking-widest opacity-70 font-semibold"
                    >{{ currentBlock.keterampilan.nama }}</span
                >
                <h1 class="text-headline-md font-bold">
                    {{ currentBlock.bagianSoal.nama }}
                </h1>
            </div>
            <div class="text-right">
                <span
                    class="text-label-md uppercase tracking-widest opacity-70 font-semibold"
                    >Soal</span
                >
                <p class="text-title-lg font-bold">
                    {{ currentBlock.nomorMulai }}–{{ currentBlock.nomorMulai + currentBlock.jumlahSoal - 1 }}
                </p>
            </div>
        </div>
        <div class="px-8 py-6">
            <p class="text-label-md uppercase tracking-wider text-text-muted mb-2">
                Petunjuk Pengerjaan
            </p>
            <RichTextViewer
                :content="currentBlock.bagianSoal.petunjuk"
                class="text-body-md leading-relaxed mb-6"
            />
            <div class="grid grid-cols-2 gap-3">
                <button
                    v-if="canGoPrev"
                    @click="emit('previous')"
                    class="flex items-center justify-center gap-2 px-5 py-3 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                >
                    <IconArrowLeft :size="16" /> Kembali
                </button>
                <button
                    @click="emit('next')"
                    class="flex items-center justify-center gap-2 px-5 py-3.5 rounded-full text-label-md font-semibold bg-secondary text-on-secondary hover:bg-secondary/90 transition-all shadow-md active:scale-[0.98] duration-150 col-span-full"
                    :class="{ '!col-span-1': canGoPrev }"
                >
                    Mulai Mengerjakan <IconArrowRight :size="16" />
                </button>
            </div>
        </div>
    </div>
</template>
