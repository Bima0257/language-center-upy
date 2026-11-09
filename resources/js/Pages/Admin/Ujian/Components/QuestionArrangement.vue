<script setup>
import draggable from 'vuedraggable';
import { IconEdit, IconGripVertical } from '@tabler/icons-vue';
import { ref, watch } from 'vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    bagianUjian: { type: Object, required: true },
    items: { type: Array, default: () => [] },
    partId: { type: String, default: 'all' },
    soalPerBagian: { type: Object, default: () => ({}) },
    itemsBySection: { type: Object, default: () => ({}) },
    dirtySections: { type: Object, default: () => ({}) },
    embedded: { type: Boolean, default: false },
});

const emit = defineEmits(['sort', 'save-arrangement', 'cancel-arrangement']);
const displayItems = ref([]);
const isEditing = ref(false);

watch(() => props.items, (items) => {
    displayItems.value = items.slice();
}, { immediate: true });

watch(
    () => props.dirtySections[props.bagianUjian.id],
    (isDirty) => {
        if (!isDirty) isEditing.value = false;
    },
);

watch(
    [() => props.bagianUjian.id, () => props.partId],
    () => {
        isEditing.value = !!props.dirtySections[props.bagianUjian.id];
    },
);

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
            <div v-if="displayItems.length" class="mb-4 flex justify-end">
                <BaseButton
                    v-if="!isEditing"
                    variant="secondary"
                    size="sm"
                    @click="isEditing = true"
                >
                    <IconEdit :size="16" />
                    Edit urutan
                </BaseButton>
                <BaseButton
                    v-else-if="!dirtySections[bagianUjian.id]"
                    variant="secondary"
                    size="sm"
                    @click="isEditing = false"
                >
                    Selesai
                </BaseButton>
            </div>

            <p v-if="displayItems.length === 0" class="py-2 text-label-md text-text-muted">
                Belum ada soal di bagian ujian ini.
            </p>

            <draggable
                v-else
                :list="displayItems"
                item-key="key"
                :filter="'.bagianSoal-header'"
                handle=".drag-handle"
                :disabled="!isEditing"
                ghost-class="opacity-40"
                class="space-y-2"
                @sort="onSort"
            >
                <template #item="{ element }">
                    <div :class="element.type === 'bagianSoal'
                        ? 'bagianSoal-header select-none'
                        : [
                            'flex items-center gap-3 rounded-xl border border-outline-variant/40 bg-surface-white px-3 py-3 transition-colors',
                            isEditing ? 'cursor-grab active:cursor-grabbing hover:border-secondary/50' : '',
                        ]">
                        <div v-if="element.type === 'bagianSoal'" class="flex items-center gap-2 pt-1">
                            <span class="inline-block rounded-full bg-pastel-purple/40 px-3 py-1 text-label-md font-semibold text-text-heading">
                                {{ element.nama }}
                            </span>
                            <span class="text-label-md text-text-muted">{{ element.jumlahSoal }} soal</span>
                        </div>

                        <div v-else class="flex w-full min-w-0 items-center gap-3">
                            <IconGripVertical
                                v-if="isEditing"
                                :size="16"
                                class="drag-handle shrink-0 cursor-grab text-text-muted"
                            />
                            <span class="w-8 shrink-0 text-center text-body-md font-bold text-text-heading">
                                {{ positionOf(element.soalId) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-3 whitespace-normal text-body-md font-medium text-text-heading">
                                    {{ element.row.soal.teks_soal || 'Soal audio' }}
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
