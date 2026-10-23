<script setup>
import { Link } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { IconChevronDown, IconChevronRight, IconFileDescription, IconCheck, IconGripVertical, IconPlus } from '@tabler/icons-vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    soalPerBagian: { type: Object, default: () => ({}) },
    soalDapatDilampirkan: { type: Object, default: () => ({}) },
    expandedSections: { type: Object, default: () => ({}) },
    itemsBySection: { type: Object, default: () => ({}) },
    dirtySections: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
    'sync-sections',
    'toggle-section',
    'add-questions',
    'sort',
    'save-arrangement',
    'cancel-arrangement',
]);

function attachableFor(bagianUjianId) {
    return props.soalDapatDilampirkan[bagianUjianId] || [];
}

function keterampilanName(keterampilan) {
    return keterampilan?.nama || '';
}

function sectionOffset(bagianUjian) {
    let offset = 0;
    for (const section of props.ujian.bagian_ujian) {
        if (section.id === bagianUjian.id) break;
        offset += (props.soalPerBagian[section.id] || []).length;
    }
    return offset;
}

function positionOf(bagianUjian, soalId) {
    const urutan = props.itemsBySection[bagianUjian.id]
        ?.filter(item => item.type === 'soal')
        .map(item => item.soalId) || [];
    return urutan.indexOf(soalId) + 1 + sectionOffset(bagianUjian);
}

function soalPreview(question) {
    return (question.teks_soal || '').substring(0, 90);
}
</script>

<template>
    <BaseCard>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-title-lg font-semibold text-text-heading">Bagian Ujian &amp; Soal</h2>
            <BaseButton size="sm" @click="emit('sync-sections')">
                <IconPlus :size="16" /> Sinkronkan Bagian Ujian
            </BaseButton>
        </div>

        <div v-if="ujian.bagian_ujian?.length" class="space-y-4">
            <div v-for="bagianUjian in ujian.bagian_ujian" :key="bagianUjian.id"
                 class="border border-outline-variant/50 rounded-2xl overflow-hidden">
                <div @click="emit('toggle-section', bagianUjian.id)"
                     class="flex items-center justify-between p-4 bg-surface-container-low cursor-pointer hover:bg-surface-container transition-colors">
                    <div class="flex items-center gap-3">
                        <button class="text-text-muted">
                            <IconChevronDown v-if="expandedSections[bagianUjian.id]" :size="18" />
                            <IconChevronRight v-else :size="18" />
                        </button>
                        <IconFileDescription :size="18" class="text-secondary" />
                        <div>
                            <p class="font-semibold text-text-heading">{{ bagianUjian.judul }}</p>
                            <p class="text-text-muted text-label-md">
                                {{ keterampilanName(bagianUjian.keterampilan) }}<span v-if="bagianUjian.bank_soal"> — {{ bagianUjian.bank_soal.nama }}</span> — {{ (soalPerBagian[bagianUjian.id] || []).length }} soal terpasang
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="attachableFor(bagianUjian.id).length > 0"
                        @click.stop="emit('add-questions', bagianUjian)"
                        class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                        <IconPlus :size="16" /> Tambah Soal ({{ attachableFor(bagianUjian.id).length }})
                    </button>
                </div>

                <div v-if="expandedSections[bagianUjian.id]" class="p-4 border-t border-outline-variant/30 space-y-5">
                    <!-- SORT ORDER: DRAG & DROP -->
                    <p v-if="(itemsBySection[bagianUjian.id] || []).length === 0" class="text-label-md text-text-muted py-2">
                        Belum ada soal di bagianUjian ini.
                    </p>
                    <draggable
                        :list="itemsBySection[bagianUjian.id]"
                        item-key="key"
                        :filter="'.bagianSoal-header,.materiSoal-header'"
                        ghost-class="opacity-40"
                        class="space-y-2"
                        @sort="emit('sort', bagianUjian.id)"
                    >
                        <template #item="{ element }">
                            <div :class="element.type === 'bagianSoal'
                                ? 'bagianSoal-header select-none'
                                : element.type === 'materiSoal'
                                    ? 'materiSoal-header select-none'
                                    : 'flex items-center gap-3 bg-surface-white border border-outline-variant/40 rounded-xl px-3 py-2 cursor-grab active:cursor-grabbing hover:border-secondary/50 transition-colors'">
                                <!-- PART HEADER (non-draggable separator) -->
                                <div v-if="element.type === 'bagianSoal'" class="flex items-center gap-2 pt-1">
                                    <span class="inline-block bg-pastel-purple/40 text-primary px-3 py-1 rounded-full text-label-md font-semibold">{{ element.nama }}</span>
                                    <span class="text-label-md text-text-muted">{{ element.jumlahSoal }} soal</span>
                                </div>
                                <!-- PASSAGE SUB-HEADER (non-draggable) -->
                                <div v-else-if="element.type === 'materiSoal'"
                                     class="bg-surface-container-low rounded-xl px-3 py-2 border border-outline-variant/30">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-body-md font-semibold text-text-heading flex items-center gap-2 min-w-0">
                                            📄 <span class="truncate">{{ element.materiSoal?.judul || 'Materi' }}</span>
                                            <span class="text-label-md text-text-muted shrink-0">— {{ element.jumlahSoal }} soal</span>
                                        </span>
                                        <Link :href="route('pustaka-soal.pratinjau', element.soalPertamaId)"
                                              class="text-secondary text-label-md font-medium hover:underline shrink-0">
                                            Lihat Detail
                                        </Link>
                                    </div>
                                </div>
                                <!-- QUESTION ROW (draggable) -->
                                <div v-else class="flex items-center gap-3 w-full">
                                    <IconGripVertical :size="16" class="text-text-muted shrink-0" />
                                    <span class="w-8 text-center font-bold text-primary text-body-md shrink-0">{{ positionOf(bagianUjian, element.soalId) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-body-md text-text-heading font-medium truncate">{{ soalPreview(element.row.soal) }}</p>
                                        <p v-if="element.row.soal.materi_soal" class="text-label-md text-text-muted truncate">📄 {{ element.row.soal.materi_soal.judul }}</p>
                                    </div>
                                 <span v-if="element.row.soal.materi_soal?.url_audio"
                                      class="text-label-md text-text-muted shrink-0">🎧</span>
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <div v-if="dirtySections[bagianUjian.id]" class="flex flex-wrap items-center gap-3 pt-2">
                        <BaseButton size="sm" @click="emit('save-arrangement', bagianUjian)">
                            <IconCheck :size="16" /> Simpan Susunan
                        </BaseButton>
                        <BaseButton variant="secondary" size="sm" @click="emit('cancel-arrangement', bagianUjian)">
                            Batal
                        </BaseButton>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-10">
            <p class="text-text-muted text-text-body text-body-md">Belum ada bagianUjian.</p>
        </div>
    </BaseCard>
</template>
