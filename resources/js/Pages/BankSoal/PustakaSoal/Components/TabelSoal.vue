<script setup>
import { IconEdit, IconEye, IconHeadphones, IconPhoto, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';

const props = defineProps({
    soal: { type: Array, default: () => [] },
    bankLocked: { type: Boolean, default: false },
});

const emit = defineEmits(['delete']);

function isLocked(question) {
    return props.bankLocked || Number(question.part_ujian_count || 0) > 0;
}

function previewUrl(question) {
    return route('preview.exam-take', {
        bank_soal_id: question.bank_soal_id,
        program_tes_id: question.bank_soal?.program_tes_id,
        soal_id: question.id,
    });
}

function teksSoal(question) {
    if (question.teks_soal) return question.teks_soal;
    if (question.materi_soal?.url_audio) return 'Soal audio';
    if (question.materi_soal?.url_gambar) return 'Soal gambar';

    return '—';
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                    <th class="w-12 px-5 py-4 text-center text-label-md font-semibold text-text-muted uppercase tracking-wider">No.</th>
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Soal</th>
                    <th class="w-px whitespace-nowrap px-5 py-4 text-center text-label-md font-semibold text-text-muted uppercase tracking-wider">Kunci</th>
                    <th class="w-px min-w-20 whitespace-nowrap px-5 py-4 text-center text-label-md font-semibold text-text-muted uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(question, indeks) in soal" :key="question.id"
                    class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-5 py-4 text-center text-body-md text-text-muted">{{ indeks + 1 }}</td>
                    <td class="px-5 py-4 min-w-[260px] max-w-md">
                        <div
                            v-if="question.materi_soal?.url_audio || question.materi_soal?.url_gambar"
                            class="mb-1 flex flex-wrap items-center gap-1"
                        >
                            <span
                                v-if="question.materi_soal?.url_audio"
                                class="inline-flex items-center gap-1 bg-pastel-blue/50 text-info px-2 py-0.5 rounded-full text-label-md font-medium"
                            >
                                <IconHeadphones :size="12" /> Audio
                            </span>
                            <span
                                v-if="question.materi_soal?.url_gambar"
                                class="inline-flex items-center gap-1 bg-pastel-purple/50 text-text-heading px-2 py-0.5 rounded-full text-label-md font-medium"
                            >
                                <IconPhoto :size="12" /> Gambar
                            </span>
                        </div>
                        <p class="text-text-heading text-body-md font-medium line-clamp-2">{{ teksSoal(question) }}</p>
                    </td>
                    <td class="w-px whitespace-nowrap px-5 py-4 text-center">
                        <BaseBadge variant="pastel-blue">{{ question.kunci_jawaban || '-' }}</BaseBadge>
                    </td>
                    <td class="w-px min-w-20 whitespace-nowrap px-5 py-4 text-center">
                        <div class="flex min-w-20 items-center justify-center gap-1 whitespace-nowrap">
                            <BaseButton
                                :href="previewUrl(question)"
                                variant="secondary"
                                icon-only
                                title="Pratinjau"
                                aria-label="Pratinjau soal"
                            >
                                <IconEye :size="16" />
                            </BaseButton>
                            <BaseButton
                                v-if="!isLocked(question)"
                                :href="route('pustaka-soal.ubah', question.id)"
                                variant="secondary"
                                icon-only
                                title="Ubah"
                                aria-label="Ubah soal"
                            >
                                <IconEdit :size="16" />
                            </BaseButton>
                            <BaseButton
                                v-else
                                variant="secondary"
                                icon-only
                                disabled
                                title="Tidak bisa diubah karena bank soal sudah digunakan di ujian"
                                aria-label="Tidak bisa diubah karena bank soal sudah digunakan di ujian"
                            >
                                <IconEdit :size="16" />
                            </BaseButton>
                            <BaseButton
                                variant="danger"
                                icon-only
                                :disabled="isLocked(question)"
                                :title="isLocked(question) ? 'Tidak bisa dihapus karena bank soal sudah digunakan di ujian' : 'Hapus'"
                                :aria-label="isLocked(question) ? 'Tidak bisa dihapus karena bank soal sudah digunakan di ujian' : 'Hapus'"
                                @click="emit('delete', question.id, teksSoal(question))"
                            >
                                <IconTrash :size="16" />
                            </BaseButton>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
