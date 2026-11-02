<script setup>
import { Link } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { IconFileDescription, IconGripVertical } from '@tabler/icons-vue';
import { ref, watch } from 'vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    bagianUjian: { type: Object, required: true },
    items: { type: Array, default: () => [] },
    partId: { type: String, default: 'all' },
    partLabel: { type: String, default: 'Semua Bagian' },
    soalPerBagian: { type: Object, default: () => ({}) },
    itemsBySection: { type: Object, default: () => ({}) },
    dirtySections: { type: Object, default: () => ({}) },
    embedded: { type: Boolean, default: false },
});

const emit = defineEmits(['sort', 'save-arrangement', 'cancel-arrangement']);
const displayItems = ref([]);

watch(() => props.items, (items) => {
    displayItems.value = items.slice();
}, { immediate: true });

function attachedCount() {
    return (props.soalPerBagian[props.bagianUjian.id] || []).length;
}

function sectionOffset() {
    let offset = 0;

    for (const section of props.ujian.bagian_ujian || []) {
        if (section.id === props.bagianUjian.id) break;
        offset += (props.soalPerBagian[section.id] || []).length;
    }

    return offset;
}

function positionOf(soalId) {
    const currentOrder = (props.itemsBySection[props.bagianUjian.id] || [])
        .filter((item) => item.type === 'soal')
        .map((item) => item.soalId);

    return currentOrder.indexOf(soalId) + 1 + sectionOffset();
}

function visibleCount() {
    return displayItems.value.filter((item) => item.type === 'soal').length;
}

function onSort() {
    emit(
        'sort',
        props.bagianUjian.id,
        props.partId,
        displayItems.value
            .filter((item) => item.type === 'soal')
            .map((item) => item.soalId),
    );
}
</script>

<template>
    <section
        :id="`bagian-ujian-panel-${bagianUjian.id}`"
        role="tabpanel"
        :aria-label="bagianUjian.judul"
    >
        <BaseCard
            :padding="embedded ? 'p-6' : true"
            :class="embedded ? 'rounded-none border-0 shadow-none' : ''"
        >
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <IconFileDescription :size="20" class="shrink-0 text-secondary" />
                    <div class="min-w-0">
                        <h3 class="truncate text-title-md font-semibold text-text-heading">
                            {{ bagianUjian.keterampilan?.nama || bagianUjian.judul }} / {{ partLabel }}
                        </h3>
                        <p class="text-label-md text-text-muted">
                            <span v-if="bagianUjian.bank_soal"> - {{ bagianUjian.bank_soal.nama }}</span>
                            - {{ visibleCount() }} soal ditampilkan
                            <span v-if="partId !== 'all'"> dari {{ attachedCount() }} soal</span>
                        </p>
                    </div>
                </div>
                <span class="rounded-full bg-pastel-blue/50 px-3 py-1 text-label-md font-medium text-text-heading">
                    Hanya atur nomor
                </span>
            </div>

            <p v-if="displayItems.length === 0" class="py-2 text-label-md text-text-muted">
                Belum ada soal di bagian ujian ini.
            </p>

            <draggable
                v-else
                :list="displayItems"
                item-key="key"
                :filter="'.bagianSoal-header,.materiSoal-header'"
                ghost-class="opacity-40"
                class="space-y-2"
                @sort="onSort"
            >
                <template #item="{ element }">
                    <div :class="element.type === 'bagianSoal'
                        ? 'bagianSoal-header select-none'
                        : element.type === 'materiSoal'
                            ? 'materiSoal-header select-none'
                            : 'flex items-center gap-3 rounded-xl border border-outline-variant/40 bg-surface-white px-3 py-3 cursor-grab active:cursor-grabbing hover:border-secondary/50 transition-colors'">
                        <div v-if="element.type === 'bagianSoal'" class="flex items-center gap-2 pt-1">
                            <span class="inline-block rounded-full bg-pastel-purple/40 px-3 py-1 text-label-md font-semibold text-text-heading">
                                {{ element.nama }}
                            </span>
                            <span class="text-label-md text-text-muted">{{ element.jumlahSoal }} soal</span>
                        </div>

                        <div
                            v-else-if="element.type === 'materiSoal'"
                            class="rounded-xl border border-outline-variant/30 bg-surface-container-low px-3 py-2"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="flex min-w-0 items-center gap-2 text-body-md font-semibold text-text-heading">
                                    <span aria-hidden="true">[materi]</span>
                                    <span class="truncate">{{ element.materiSoal?.judul || 'Materi' }}</span>
                                    <span class="shrink-0 text-label-md text-text-muted">- {{ element.jumlahSoal }} soal</span>
                                </span>
                                <Link
                                    :href="route('pustaka-soal.pratinjau', element.soalPertamaId)"
                                    class="shrink-0 text-label-md font-medium text-secondary hover:underline"
                                >
                                    Lihat Detail
                                </Link>
                            </div>
                        </div>

                        <div v-else class="flex w-full min-w-0 items-center gap-3">
                            <IconGripVertical :size="16" class="shrink-0 text-text-muted" />
                            <span class="w-8 shrink-0 text-center text-body-md font-bold text-text-heading">
                                {{ positionOf(element.soalId) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-3 whitespace-normal text-body-md font-medium text-text-heading">
                                    {{ element.row.soal.teks_soal || 'Soal audio' }}
                                </p>
                                <p v-if="element.row.soal.materi_soal" class="truncate text-label-md text-text-muted">
                                    {{ element.row.soal.materi_soal.judul }}
                                </p>
                            </div>
                            <span v-if="element.row.soal.materi_soal?.url_audio" class="shrink-0 text-label-md text-text-muted">
                                Audio
                            </span>
                        </div>
                    </div>
                </template>
            </draggable>

            <div v-if="dirtySections[bagianUjian.id]" class="pt-4">
                <BaseFormActions
                    save-type="button"
                    @save="emit('save-arrangement', bagianUjian)"
                    @cancel="emit('cancel-arrangement', bagianUjian)"
                />
            </div>
        </BaseCard>
    </section>
</template>
