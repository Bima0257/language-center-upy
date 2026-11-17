<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import TabelSoal from './TabelSoal.vue';
import PenampilMediaMateriSoal from './PenampilMediaMateriSoal.vue';
import {
    IconChevronDown,
    IconFileDescription,
    IconEye,
    IconEdit,
    IconPlus,
    IconTrash,
} from '@tabler/icons-vue';

const props = defineProps({
    group: { type: Object, required: true },
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

const isOpen = ref(true);
const panelId = computed(() => `materi-soal-panel-${props.group.materiSoal.id}`);
</script>

<template>
    <BaseCard
        :padding="false"
        class="overflow-hidden"
        :class="attached ? 'rounded-none border-0 shadow-none' : ''"
    >
        <div class="border-b border-outline-variant/30 bg-surface-container-low/60 px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="flex min-w-0 items-center gap-2 rounded-xl text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                    :aria-expanded="isOpen"
                    :aria-controls="panelId"
                    :title="isOpen ? 'Sembunyikan soal materi' : 'Tampilkan soal materi'"
                    @click="isOpen = !isOpen"
                >
                    <IconChevronDown
                        :size="18"
                        class="shrink-0 text-text-muted transition-transform"
                        :class="!isOpen ? '-rotate-90' : ''"
                    />
                    <IconFileDescription :size="20" class="shrink-0 text-secondary" />
                    <span role="heading" aria-level="3" class="truncate text-title-md font-semibold text-text-heading">
                        {{ group.materiSoal.judul }}
                    </span>
                </button>
                <span class="inline-block rounded-full bg-pastel-purple/50 px-2.5 py-0.5 text-label-md font-medium text-text-heading">
                    {{ materiSoalTypeLabelFn(group.materiSoal) }}
                </span>
                <span class="text-label-md text-text-muted">{{ group.soal.length }} soal</span>
                <div class="ml-auto flex items-center gap-1">
                    <Link
                        v-if="group.soal.length"
                        :href="route('preview.exam-take', {
                            bank_soal_id: group.soal[0].bank_soal_id,
                            program_tes_id: group.soal[0].bank_soal?.program_tes_id,
                            soal_id: group.soal[0].id,
                        })"
                        class="p-1.5 text-text-muted transition-colors hover:text-secondary"
                        title="Pratinjau soal materi ini"
                    >
                        <IconEye :size="16" />
                    </Link>
                    <button
                        type="button"
                        :disabled="bankLocked"
                        class="p-1.5 text-text-muted transition-colors hover:text-secondary disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:text-text-muted"
                        title="Tambah soal pada materi"
                        aria-label="Tambah soal pada materi"
                        @click="$emit('add-soal', group.materiSoal)"
                    >
                        <IconPlus :size="16" />
                    </button>
                    <button
                        type="button"
                        :disabled="bankLocked"
                        :title="bankLocked ? 'Tidak bisa diubah karena bank soal sudah digunakan di ujian.' : 'Ubah materi soal'"
                        class="p-1.5 text-text-muted transition-colors hover:text-secondary disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:text-text-muted"
                        @click="$emit('start-edit', group.materiSoal)"
                    >
                        <IconEdit :size="16" />
                    </button>
                    <button
                        type="button"
                        :disabled="bankLocked || group.soal.length > 0"
                        :title="bankLocked
                            ? 'Tidak bisa dihapus karena bank soal sudah digunakan di ujian.'
                            : group.soal.length > 0
                                ? `Tidak bisa dihapus — materi soal dipakai ${group.soal.length} soal`
                                : 'Hapus materi soal'"
                        class="p-1.5 text-text-muted transition-colors hover:text-error-red disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:text-text-muted"
                        @click="$emit('delete-materi-soal', group.materiSoal)"
                    >
                        <IconTrash :size="16" />
                    </button>
                </div>
            </div>

            <PenampilMediaMateriSoal
                v-if="isOpen"
                :materi-soal="group.materiSoal"
                :expanded="isMateriSoalExpandedFn(group.materiSoal.id)"
                @toggle-expand="toggleMateriSoalFn(group.materiSoal.id)"
            />
        </div>

        <div v-if="isOpen" :id="panelId">
            <TabelSoal
                :soal="group.soal"
                :bank-locked="bankLocked"
                @delete="(...args) => $emit('soal-delete', ...args)"
            />
        </div>
    </BaseCard>
</template>
