<script setup>
import InputPilihan from '@/Pages/BankSoal/Components/InputPilihan.vue';
import PemilihKunciJawaban from '@/Pages/BankSoal/Components/PemilihKunciJawaban.vue';
import { IconTrash, IconInfoCircle, IconHeadphones } from '@tabler/icons-vue';

const props = defineProps({
    question: { type: Object, required: true },
    questionIndex: { type: Number, default: 0 },
    questionCount: { type: Number, default: 1 },
    showAdd: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    isAudio: { type: Boolean, default: false },
    materiSoalMode: { type: String, default: 'none' },
    optionKeys: { type: Array, default: () => ['A', 'B', 'C', 'D'] },
});

const emit = defineEmits(['add', 'remove']);

function errorFor(field) {
    return props.errors[`soal.${props.questionIndex}.${field}`] || '';
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-outline-variant/50 bg-surface-white shadow-standard">
        <div class="flex items-center justify-between gap-3 px-6 pb-2 pt-5">
            <div class="flex items-center gap-2">
                <p class="text-title-lg font-semibold text-text-heading">Soal {{ questionIndex + 1 }}</p>
                <span v-if="isAudio" class="inline-flex items-center gap-1 rounded-full bg-pastel-purple/30 px-2.5 py-0.5 text-label-md font-medium text-primary">
                    <IconHeadphones :size="14" /> Audio
                </span>
            </div>
            <div class="flex items-center gap-2">
                <BaseTambahData v-if="showAdd" @click="emit('add')" />
                <button
                    type="button"
                    :aria-disabled="questionCount <= 1"
                    @click="questionCount > 1 && emit('remove')"
                    class="rounded-lg border border-error-red/20 bg-error-container/30 p-2 text-error-red transition-colors hover:bg-error-container/60"
                    title="Hapus soal"
                    aria-label="Hapus soal"
                >
                    <IconTrash :size="16" />
                </button>
            </div>
        </div>

        <div class="px-6 pb-6 pt-3">
            <template v-if="isAudio">
                <div class="space-y-4 rounded-xl border border-pastel-purple/40 bg-pastel-purple/10 p-4">
                    <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                        <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                        Soal menggunakan audio materi soal sesuai policy keterampilan. Peserta hanya memilih A/B/C/D.
                    </p>
                    <div v-if="materiSoalMode === 'none'" class="flex items-center gap-2 text-label-md text-error-red">
                        <IconHeadphones :size="16" class="shrink-0" />
                        Soal audio wajib memakai materi soal audio — buat materi soal di atas.
                    </div>
                    <PemilihKunciJawaban v-model="question.kunci_jawaban" :option-keys="optionKeys" label="Pilihan Jawaban" size="compact" />
                </div>
            </template>

            <template v-else>
                <BaseFormGrid>
                    <div class="sm:col-span-2">
                        <label class="text-body-md font-medium text-text-heading block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                        <BaseTextarea v-model="question.teks_soal" rows="4" required />
                        <p v-if="errorFor('teks_soal')" class="mt-1 text-error-red text-xs">{{ errorFor('teks_soal') }}</p>
                    </div>

                    <InputPilihan class="sm:col-span-2" :form="question" :option-keys="optionKeys" size="compact" />
                    <PemilihKunciJawaban class="sm:col-span-2" v-model="question.kunci_jawaban" :option-keys="optionKeys" size="compact" />
                </BaseFormGrid>
            </template>
        </div>
    </div>
</template>
