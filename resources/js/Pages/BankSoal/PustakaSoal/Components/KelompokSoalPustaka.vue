<script setup>
import { IconBook } from '@tabler/icons-vue';
import KartuKelompokMateriSoal from './KartuKelompokMateriSoal.vue';
import TabelSoal from './TabelSoal.vue';

defineProps({
    kelompokSoal: { type: Array, default: () => [] },
    canReview: { type: Boolean, default: false },
    materiSoalForm: { type: Object, required: true },
    editingMateriSoalId: { type: [String, null], default: null },
    selectedIds: { type: Array, default: () => [] },
    keterampilanNameFn: { type: Function, default: () => '' },
    bankNameFn: { type: Function, default: () => '' },
    optionKeys: { type: Array, default: () => [] },
    quickForm: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    addingMateriSoalId: { type: [String, null], default: null },
    quickBagianSoalFn: { type: Function, default: () => [] },
    quickIsAudio: { type: Boolean, default: false },
    labelKeterampilanFn: { type: Function, default: () => '' },
    isMateriSoalSelectedFn: { type: Function, default: () => false },
    toggleMateriSoalSelectionFn: { type: Function, default: () => {} },
    toggleMateriSoalFn: { type: Function, default: () => {} },
    materiSoalTypeLabelFn: { type: Function, default: () => 'Teks' },
    isMateriSoalExpandedFn: { type: Function, default: () => false },
});

defineEmits([
    'start-edit',
    'cancel-edit',
    'save-materi-soal',
    'add-soal',
    'close-add',
    'save-quick',
    'soal-toggle',
    'soal-review',
    'soal-delete',
    'delete-materi-soal',
]);

function bagianSoalGroupTotal(kelompokBagianSoal) {
    return (
        kelompokBagianSoal.groups.reduce((total, group) => total + group.soal.length, 0) +
        kelompokBagianSoal.standalone.length
    );
}

function keterampilanGroupTotal(kelompokKeterampilan) {
    return kelompokKeterampilan.bagianSoal.reduce(
        (total, kelompokBagianSoal) => total + bagianSoalGroupTotal(kelompokBagianSoal),
        0,
    );
}
</script>

<template>
    <div class="space-y-8">
        <template
            v-for="kelompokKeterampilan in kelompokSoal"
            :key="kelompokKeterampilan.keterampilan_id || 'no-keterampilan'"
        >
            <div class="flex items-center gap-3">
                <BaseBadge variant="primary">
                    {{ kelompokKeterampilan.label || 'Tanpa Keterampilan' }}
                </BaseBadge>
                <span class="text-label-md text-text-muted">
                    {{ keterampilanGroupTotal(kelompokKeterampilan) }} soal
                </span>
            </div>

            <div class="space-y-6">
                <template
                    v-for="kelompokBagianSoal in kelompokKeterampilan.bagianSoal"
                    :key="kelompokBagianSoal.bagianSoal?.id || 'no-bagianSoal'"
                >
                    <div class="flex items-center gap-2">
                        <BaseBadge variant="pastel">
                            {{ kelompokBagianSoal.bagianSoal?.nama || 'Tanpa Bagian Soal' }}
                        </BaseBadge>
                        <span class="text-label-md text-text-muted">
                            {{ bagianSoalGroupTotal(kelompokBagianSoal) }} soal
                        </span>
                    </div>

                    <div class="space-y-4">
                        <KartuKelompokMateriSoal
                            v-for="group in kelompokBagianSoal.groups"
                            :key="group.materiSoal.id"
                            :group="group"
                            :can-review="canReview"
                            :materi-soal-form="materiSoalForm"
                            :editing-materi-soal-id="editingMateriSoalId"
                            :selected-ids="selectedIds"
                            :keterampilan-name-fn="keterampilanNameFn"
                            :bank-name-fn="bankNameFn"
                            :option-keys="optionKeys"
                            :quick-form="quickForm"
                            :bank-soal="bankSoal"
                            :adding-materi-soal-id="addingMateriSoalId"
                            :quick-bagian-soal-fn="quickBagianSoalFn"
                            :quick-is-audio="quickIsAudio"
                            :label-keterampilan-fn="labelKeterampilanFn"
                            :is-materi-soal-selected-fn="isMateriSoalSelectedFn"
                            :toggle-materi-soal-selection-fn="toggleMateriSoalSelectionFn"
                            :toggle-materi-soal-fn="toggleMateriSoalFn"
                            :materi-soal-type-label-fn="materiSoalTypeLabelFn"
                            :is-materi-soal-expanded-fn="isMateriSoalExpandedFn"
                            @start-edit="$emit('start-edit', $event)"
                            @cancel-edit="$emit('cancel-edit')"
                            @save-materi-soal="$emit('save-materi-soal', $event)"
                            @add-soal="$emit('add-soal', $event)"
                            @close-add="$emit('close-add')"
                            @save-quick="$emit('save-quick')"
                            @soal-toggle="$emit('soal-toggle', $event)"
                            @soal-review="(...args) => $emit('soal-review', ...args)"
                            @soal-delete="(...args) => $emit('soal-delete', ...args)"
                            @delete-materi-soal="$emit('delete-materi-soal', $event)"
                        />

                        <div v-if="kelompokBagianSoal.standalone.length">
                            <div class="flex items-center gap-2 px-1 mb-3">
                                <IconBook :size="16" class="text-text-muted" />
                                <p class="text-label-md font-semibold text-text-muted uppercase tracking-wider">
                                    Soal Standalone
                                </p>
                            </div>
                            <BaseCard :padding="false" class="overflow-hidden">
                                <TabelSoal
                                    :soal="kelompokBagianSoal.standalone"
                                    :can-review="canReview"
                                    :keterampilan-name-fn="keterampilanNameFn"
                                    :bank-name-fn="bankNameFn"
                                    :selected-ids="selectedIds"
                                    @toggle="$emit('soal-toggle', $event)"
                                    @review="(...args) => $emit('soal-review', ...args)"
                                    @delete="(...args) => $emit('soal-delete', ...args)"
                                />
                            </BaseCard>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</template>
