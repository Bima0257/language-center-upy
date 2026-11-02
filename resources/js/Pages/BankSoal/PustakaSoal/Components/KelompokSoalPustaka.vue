<script setup>
import { IconBook } from '@tabler/icons-vue';
import KartuKelompokMateriSoal from './KartuKelompokMateriSoal.vue';
import TabelSoal from './TabelSoal.vue';

defineProps({
    kelompokSoal: { type: Array, default: () => [] },
    toggleMateriSoalFn: { type: Function, default: () => {} },
    materiSoalTypeLabelFn: { type: Function, default: () => 'Teks' },
    isMateriSoalExpandedFn: { type: Function, default: () => false },
    attached: { type: Boolean, default: false },
    bankLocked: { type: Boolean, default: false },
});

defineEmits([
    'start-edit',
    'add-soal',
    'soal-delete',
    'delete-materi-soal',
]);

</script>

<template>
    <div class="space-y-8">
        <template
            v-for="kelompokKeterampilan in kelompokSoal"
            :key="kelompokKeterampilan.keterampilan_id || 'no-keterampilan'"
        >
            <div class="space-y-6">
                <template
                    v-for="kelompokBagianSoal in kelompokKeterampilan.bagianSoal"
                    :key="kelompokBagianSoal.bagianSoal?.id || 'no-bagianSoal'"
                >
                    <div class="space-y-4">
                        <KartuKelompokMateriSoal
                            v-for="group in kelompokBagianSoal.groups"
                            :key="group.materiSoal.id"
                            :group="group"
                            :toggle-materi-soal-fn="toggleMateriSoalFn"
                            :materi-soal-type-label-fn="materiSoalTypeLabelFn"
                            :is-materi-soal-expanded-fn="isMateriSoalExpandedFn"
                            :attached="attached"
                            :bank-locked="bankLocked"
                            @start-edit="$emit('start-edit', $event)"
                            @add-soal="$emit('add-soal', $event)"
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
                            <BaseCard
                                :padding="false"
                                class="overflow-hidden"
                                :class="attached ? 'rounded-none border-0 shadow-none' : ''"
                            >
                                <TabelSoal
                                    :soal="kelompokBagianSoal.standalone"
                                    :bank-locked="bankLocked"
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
