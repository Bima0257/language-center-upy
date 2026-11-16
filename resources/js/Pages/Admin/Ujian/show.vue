<script setup>
import { Head, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed, ref, watch } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import QuestionArrangement from './Components/QuestionArrangement.vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    partSoal: { type: Array, default: () => [] },
    partSoalPerPart: { type: Object, default: () => ({}) },
    soalPerPart: { type: Object, default: () => ({}) },
});

const activeSectionId = ref(String(props.ujian.part_ujian?.[0]?.id || ''));
const activePartId = ref('all');
const localOrders = ref({});
const itemsBySection = ref({});
const dirtySections = ref({});

const sectionTabs = computed(() => (props.ujian.part_ujian || []).map((partUjian) => ({
    value: String(partUjian.id),
    label: `${partUjian.section?.nama || partUjian.judul || 'Section'} (${questionRowsFor(partUjian.id).length})`,
})));

const activeSection = computed(() => (props.ujian.part_ujian || []).find(
    (partUjian) => String(partUjian.id) === activeSectionId.value,
));

function questionRowsFor(partUjianId) {
    return props.soalPerPart[partUjianId] || [];
}

function partIdsFor(partUjianId) {
    const pivotPartIds = (props.partSoalPerPart[partUjianId] || [])
        .slice()
        .sort((left, right) => (left.urutan || 0) - (right.urutan || 0))
        .map((row) => String(row.part_soal_id));
    const questionPartIds = questionRowsFor(partUjianId)
        .map((row) => String(row.soal?.part_soal_id || 'none'));

    return [...new Set([...pivotPartIds, ...questionPartIds])];
}

function partNameFor(partId) {
    if (partId === 'none') return 'Tanpa Part';

    return props.partSoal.find((part) => String(part.id) === partId)?.nama || 'Part Soal';
}

function firstPartIdFor(partUjianId) {
    return partIdsFor(partUjianId)[0] || 'all';
}

const partTabs = computed(() => {
    if (!activeSection.value) return [];

    const partUjianId = activeSection.value.id;
    const questionRows = questionRowsFor(partUjianId);

    return [
        {
            value: 'all',
            label: `Semua (${questionRows.length})`,
        },
        ...partIdsFor(partUjianId).map((partId) => ({
            value: partId,
            label: `${partNameFor(partId)} (${questionRows.filter((row) => String(row.soal?.part_soal_id || 'none') === partId).length})`,
        })),
    ];
});

function savedOrderFor(partUjianId) {
    return (props.soalPerPart[partUjianId] || [])
        .slice()
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0))
        .map((row) => row.soal_id);
}

function updateDirty(partUjianId) {
    const current = localOrders.value[partUjianId] || [];
    const saved = savedOrderFor(partUjianId);
    dirtySections.value[partUjianId] = JSON.stringify(current) !== JSON.stringify(saved);
}

function initLocalOrders(partUjianId) {
    localOrders.value[partUjianId] = savedOrderFor(partUjianId);
}

function buildItems(partUjianId) {
    const urutan = localOrders.value[partUjianId] || [];
    const byId = {};

    for (const row of props.soalPerPart[partUjianId] || []) {
        byId[row.soal_id] = row;
    }

    const items = [];
    let currentPartKey = null;

    for (const questionId of urutan) {
        const row = byId[questionId];
        if (!row) continue;

        const partSoalId = row.soal?.part_soal_id ? String(row.soal.part_soal_id) : 'none';
        if (partSoalId !== currentPartKey) {
            currentPartKey = partSoalId;
            const partSoal = partSoalId === 'none'
                ? null
                : props.partSoal.find((part) => String(part.id) === partSoalId);

            items.push({
                type: 'partSoal',
                key: partSoalId === 'none' ? 'partSoal-none' : `partSoal-${partSoalId}`,
                partSoalId,
                nama: partSoal?.nama || (partSoalId === 'none' ? 'Tanpa Part Soal' : 'Part Soal'),
                jumlahSoal: 0,
            });
        }

        items.push({ type: 'soal', key: `q-${questionId}`, partSoalId, soalId: questionId, row });
    }

    const counts = {};
    for (const item of items) {
        if (item.type !== 'soal') continue;
        const partId = item.row.soal?.part_soal_id ? String(item.row.soal.part_soal_id) : 'none';
        counts[partId] = (counts[partId] || 0) + 1;
    }

    for (const item of items) {
        if (item.type === 'partSoal') {
            item.jumlahSoal = counts[item.key.replace('partSoal-', '')] || 0;
        }
    }

    return items;
}

function initItems(partUjianId) {
    itemsBySection.value[partUjianId] = buildItems(partUjianId);
}

function initializeSection(partUjianId) {
    if (!partUjianId) return;

    initLocalOrders(partUjianId);
    initItems(partUjianId);
    dirtySections.value[partUjianId] = false;
}

function selectSection(value) {
    activeSectionId.value = String(value);
}

function selectPart(value) {
    activePartId.value = String(value);
}

function questionPartId(partUjianId, questionId) {
    const row = questionRowsFor(partUjianId).find((item) => item.soal_id === questionId);

    return String(row?.soal?.part_soal_id || 'none');
}

const visibleItems = computed(() => {
    const items = itemsBySection.value[activeSectionId.value] || [];

    if (activePartId.value === 'all') return items;

    return items.filter((item) => item.type === 'soal'
        && item.partSoalId === activePartId.value);
});

function onSectionSort(partUjianId, partId, orderedQuestionIds) {
    if (partId === 'all') {
        localOrders.value[partUjianId] = orderedQuestionIds;
    } else {
        let questionIndex = 0;
        localOrders.value[partUjianId] = (localOrders.value[partUjianId] || []).map((questionId) => {
            if (questionPartId(partUjianId, questionId) !== partId) return questionId;

            return orderedQuestionIds[questionIndex++];
        });
    }

    initItems(partUjianId);
    updateDirty(partUjianId);
}

function cancelArrangement(partUjian) {
    initializeSection(partUjian.id);
}

function saveArrangement(partUjian) {
    const urutan = localOrders.value[partUjian.id] || [];
    const soalOrders = urutan.map((soalId, index) => ({
        soal_id: soalId,
        nomor: index + 1,
    }));

    router.patch(route('admin.ujian.part.susunan', [props.ujian.id, partUjian.id]), {
        urutan_soal: soalOrders,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            initializeSection(partUjian.id);
        },
    });
}

watch(activeSectionId, (value) => {
    activePartId.value = firstPartIdFor(value);
    initializeSection(value);
}, { immediate: true });

watch(partTabs, (tabs) => {
    if (!tabs.some((tab) => tab.value === activePartId.value)) {
        activePartId.value = tabs[0]?.value || 'all';
    }
}, { immediate: true });
</script>

<template>
    <Head :title="ujian.judul" />
    <TataLetakDasbor
        :title="ujian.judul"
    >
        <template #header-actions>
            <BaseButton
                :href="route('admin.ujian.pratinjau', ujian.id)"
                variant="secondary"
                size="sm"
            >
                <IconEye :size="16" />
                Pratinjau Ujian
            </BaseButton>
        </template>

        <div>
            <div class="relative">
                <div class="mb-4">
                    <h2 class="text-title-lg font-semibold text-text-heading">Soal Ujian</h2>
                    <p class="mt-1 text-body-md text-text-muted">
                        Pilih section dan part soal. Soal hanya dapat diurutkan di dalam part aktif.
                    </p>
                </div>

                <div
                    v-if="sectionTabs.length"
                    class="flex min-w-0 items-end gap-1"
                >
                    <div class="min-w-0 flex-1">
                        <BaseTabsChrome
                            :items="sectionTabs"
                            :model-value="activeSectionId"
                            aria-label="Section ujian"
                            class="relative z-10 -mb-px px-5"
                            @update:model-value="selectSection"
                        />
                    </div>
                </div>
            </div>

            <div v-if="activeSection" class="min-w-0">
                <BaseCard :padding="false" class="!border-t-0 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3">
                        <div class="inline-flex min-w-0 max-w-full items-center gap-1">
                            <BaseTabs
                                :items="partTabs"
                                :model-value="activePartId"
                                aria-label="Part soal"
                                class="min-w-0 max-w-full"
                                @update:model-value="selectPart"
                            />
                        </div>
                    </div>

                    <QuestionArrangement
                        :ujian="ujian"
                        :part-ujian="activeSection"
                        :soal-per-part="soalPerPart"
                        :items="visibleItems"
                        :part-id="activePartId"
                        :items-by-section="itemsBySection"
                        :dirty-sections="dirtySections"
                        embedded
                        @sort="onSectionSort"
                        @save-arrangement="saveArrangement"
                        @cancel-arrangement="cancelArrangement"
                    />
                </BaseCard>
            </div>

            <div v-else class="py-10 text-center text-body-md text-text-muted">
                Belum ada part ujian.
            </div>
        </div>
    </TataLetakDasbor>
</template>
