<script setup>
import InputPilihan from '@/Pages/BankSoal/Components/InputPilihan.vue';
import PemilihKunciJawaban from '@/Pages/BankSoal/Components/PemilihKunciJawaban.vue';
import { IconPlus, IconTrash, IconInfoCircle, IconHeadphones } from '@tabler/icons-vue';

defineProps({
    question: { type: Object, required: true },
    questionIndex: { type: Number, default: 0 },
    questionCount: { type: Number, default: 1 },
    isAudio: { type: Boolean, default: false },
    materiSoalMode: { type: String, default: 'none' },
    optionKeys: { type: Array, default: () => ['A', 'B', 'C', 'D'] },
});

const emit = defineEmits(['add', 'remove']);
</script>

<template>
    <div class="border border-outline-variant/50 rounded-2xl p-5 space-y-4 relative">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <p class="text-label-md font-semibold text-primary">Soal {{ questionIndex + 1 }}</p>
                <span v-if="isAudio" class="inline-flex items-center gap-1 bg-pastel-purple/30 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">
                    <IconHeadphones :size="14" /> Audio
                </span>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" @click="emit('add')"
                        class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Tambah soal setelah ini">
                    <IconPlus :size="16" />
                </button>
                <button v-if="questionCount > 1" type="button" @click="emit('remove')"
                        class="p-1.5 text-text-muted hover:text-error-red transition-colors" title="Hapus soal">
                    <IconTrash :size="16" />
                </button>
            </div>
        </div>

        <template v-if="isAudio">
            <div class="bg-pastel-purple/10 border border-pastel-purple/40 rounded-2xl p-4 space-y-4">
                <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                    <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                    Soal, materi, dan pilihan jawaban berada di audio materiSoal. Peserta hanya memilih A/B/C/D.
                </p>
                <div v-if="materiSoalMode === 'none'" class="flex items-center gap-2 text-label-md text-error-red">
                    <IconHeadphones :size="16" class="shrink-0" />
                    Soal listening wajib memakai materiSoal audio — pilih atau buat materi soal di atas.
                </div>
                <PemilihKunciJawaban v-model="question.kunci_jawaban" :option-keys="optionKeys" label="Pilihan Jawaban" />
            </div>
        </template>

        <template v-else>
            <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                <BaseTextarea v-model="question.teks_soal" rows="2" required /></div>

            <InputPilihan :form="question" :option-keys="optionKeys" />
            <PemilihKunciJawaban v-model="question.kunci_jawaban" :option-keys="optionKeys" />
        </template>
    </div>
</template>
