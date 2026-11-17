<script setup>
import { IconX } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseInfoField from '@/Components/Base/BaseInfoField.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';
import { formatProgramTes } from '@/constants/programTes';

defineProps({
    show: { type: Boolean, default: false },
    bankSoal: { type: Object, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <BaseModal
        :show="show"
        max-width="2xl"
        aria-labelledby="detail-bank-soal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="detail-bank-soal-title" class="text-headline-md font-bold text-text-heading">Detail Bank Soal</h2>
                        <button type="button" aria-label="Tutup detail bank soal" class="p-2 text-text-muted transition-colors hover:text-primary" @click="emit('close')">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <div v-if="bankSoal" class="space-y-5 px-8 pb-8 pt-6">
                <div class="flex flex-wrap items-center gap-3">
                    <h3 class="text-title-lg font-semibold text-text-heading">{{ bankSoal.nama }}</h3>
                    <BaseBadge :variant="bankSoal.aktif ? 'success' : 'neutral'">
                        {{ bankSoal.aktif ? 'Aktif' : 'Nonaktif' }}
                    </BaseBadge>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <BaseInfoField label="Program Tes" :value="formatProgramTes(bankSoal.program_tes)" />
                    <BaseInfoField label="Jumlah Soal" :value="bankSoal.soal_count ?? 0" />
                    <BaseInfoField label="Part Ujian" :value="bankSoal.part_ujian_count ?? 0" />
                    <BaseInfoField label="Total Section Aktif" :value="bankSoal.program_tes?.section_aktif_count ?? 0" />
                </div>

                <div>
                    <p class="text-label-md font-medium text-text-heading">Deskripsi</p>
                    <div class="mt-2 rounded-2xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3">
                        <RichTextViewer v-if="bankSoal.deskripsi" :content="bankSoal.deskripsi" />
                        <p v-else class="text-body-md text-text-muted">Belum ada deskripsi.</p>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="flex justify-end px-8 py-4">
                    <BaseButton variant="secondary" size="sm" @click="emit('close')">Tutup</BaseButton>
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
