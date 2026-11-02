<script setup>
import { Head, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed, ref, watch } from 'vue';
import QuestionArrangement from './Components/QuestionArrangement.vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    bagianSoal: { type: Array, default: () => [] },
    bagianSoalPerBagian: { type: Object, default: () => ({}) },
    soalPerBagian: { type: Object, default: () => ({}) },
});

const activeSkillId = ref(String(props.ujian.bagian_ujian?.[0]?.id || ''));
const activePartId = ref('all');
const localOrders = ref({});
const itemsBySection = ref({});
const dirtySections = ref({});

const skillTabs = computed(() => (props.ujian.bagian_ujian || []).map((bagianUjian) => ({
    value: String(bagianUjian.id),
    label: `${bagianUjian.keterampilan?.nama || bagianUjian.judul || 'Keterampilan'} (${questionRowsFor(bagianUjian.id).length})`,
})));

const activeSection = computed(() => (props.ujian.bagian_ujian || []).find(
    (bagianUjian) => String(bagianUjian.id) === activeSkillId.value,
));

function questionRowsFor(bagianUjianId) {
    return props.soalPerBagian[bagianUjianId] || [];
}

function partIdsFor(bagianUjianId) {
    const pivotPartIds = (props.bagianSoalPerBagian[bagianUjianId] || [])
        .slice()
        .sort((left, right) => (left.urutan || 0) - (right.urutan || 0))
        .map((row) => String(row.bagian_soal_id));
    const questionPartIds = questionRowsFor(bagianUjianId)
        .map((row) => String(row.soal?.bagian_soal_id || 'none'));

    return [...new Set([...pivotPartIds, ...questionPartIds])];
}

function partNameFor(partId) {
    if (partId === 'none') return 'Tanpa Bagian';

    return props.bagianSoal.find((part) => String(part.id) === partId)?.nama || 'Bagian Soal';
}

function firstPartIdFor(bagianUjianId) {
    return partIdsFor(bagianUjianId)[0] || 'all';
}

const partTabs = computed(() => {
    if (!activeSection.value) return [];

    const bagianUjianId = activeSection.value.id;
    const questionRows = questionRowsFor(bagianUjianId);

    return [
        {
            value: 'all',
            label: `Semua (${questionRows.length})`,
        },
        ...partIdsFor(bagianUjianId).map((partId) => ({
            value: partId,
            label: `${partNameFor(partId)} (${questionRows.filter((row) => String(row.soal?.bagian_soal_id || 'none') === partId).length})`,
        })),
    ];
});

const activePartLabel = computed(() => activePartId.value === 'all'
    ? 'Semua Bagian'
    : partNameFor(activePartId.value));

function savedOrderFor(bagianUjianId) {
    return (props.soalPerBagian[bagianUjianId] || [])
        .slice()
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0))
        .map((row) => row.soal_id);
}

function updateDirty(bagianUjianId) {
    const current = localOrders.value[bagianUjianId] || [];
    const saved = savedOrderFor(bagianUjianId);
    dirtySections.value[bagianUjianId] = JSON.stringify(current) !== JSON.stringify(saved);
}

function initLocalOrders(bagianUjianId) {
    localOrders.value[bagianUjianId] = savedOrderFor(bagianUjianId);
}

function buildItems(bagianUjianId) {
    const urutan = localOrders.value[bagianUjianId] || [];
    const byId = {};

    for (const row of props.soalPerBagian[bagianUjianId] || []) {
        byId[row.soal_id] = row;
    }

    const items = [];
    let currentPartKey = null;
    let currentPassageKey = null;

    for (const questionId of urutan) {
        const row = byId[questionId];
        if (!row) continue;

        const bagianSoalId = row.soal?.bagian_soal_id ? String(row.soal.bagian_soal_id) : 'none';
        if (bagianSoalId !== currentPartKey) {
            currentPartKey = bagianSoalId;
            currentPassageKey = null;
            const bagianSoal = bagianSoalId === 'none'
                ? null
                : props.bagianSoal.find((part) => String(part.id) === bagianSoalId);

            items.push({
                type: 'bagianSoal',
                key: bagianSoalId === 'none' ? 'bagianSoal-none' : `bagianSoal-${bagianSoalId}`,
                bagianSoalId,
                nama: bagianSoal?.nama || (bagianSoalId === 'none' ? 'Tanpa Bagian Soal' : 'Bagian Soal'),
                jumlahSoal: 0,
            });
        }

        const materiSoalId = row.soal?.materi_soal_id ? String(row.soal.materi_soal_id) : null;
        const materiSoalKey = materiSoalId ? `pass-${materiSoalId}` : null;

        if (materiSoalKey !== currentPassageKey) {
            currentPassageKey = materiSoalKey;
            if (materiSoalKey) {
                items.push({
                    type: 'materiSoal',
                    key: materiSoalKey,
                    bagianSoalId,
                    materiSoal: row.soal.materi_soal,
                    soalPertamaId: questionId,
                    jumlahSoal: 0,
                });
            }
        }

        items.push({ type: 'soal', key: `q-${questionId}`, bagianSoalId, soalId: questionId, row });
    }

    const counts = {};
    for (const item of items) {
        if (item.type !== 'soal') continue;
        const partId = item.row.soal?.bagian_soal_id ? String(item.row.soal.bagian_soal_id) : 'none';
        counts[partId] = (counts[partId] || 0) + 1;
    }

    for (const item of items) {
        if (item.type === 'bagianSoal') {
            item.jumlahSoal = counts[item.key.replace('bagianSoal-', '')] || 0;
        }

        if (item.type === 'materiSoal') {
            const passageId = item.key.replace('pass-', '');
            item.jumlahSoal = urutan.filter((questionId) => {
                const row = byId[questionId];
                return row && String(row.soal?.materi_soal_id) === passageId;
            }).length;
        }
    }

    return items;
}

function initItems(bagianUjianId) {
    itemsBySection.value[bagianUjianId] = buildItems(bagianUjianId);
}

function initializeSection(bagianUjianId) {
    if (!bagianUjianId) return;

    initLocalOrders(bagianUjianId);
    initItems(bagianUjianId);
    dirtySections.value[bagianUjianId] = false;
}

function selectSection(value) {
    activeSkillId.value = String(value);
}

function selectPart(value) {
    activePartId.value = String(value);
}

function questionPartId(bagianUjianId, questionId) {
    const row = questionRowsFor(bagianUjianId).find((item) => item.soal_id === questionId);

    return String(row?.soal?.bagian_soal_id || 'none');
}

const visibleItems = computed(() => {
    const items = itemsBySection.value[activeSkillId.value] || [];

    if (activePartId.value === 'all') return items;

    return items.filter((item) => (
        item.type === 'soal' || item.type === 'materiSoal'
    ) && item.bagianSoalId === activePartId.value);
});

function onSectionSort(bagianUjianId, partId, orderedQuestionIds) {
    if (partId === 'all') {
        localOrders.value[bagianUjianId] = orderedQuestionIds;
    } else {
        let questionIndex = 0;
        localOrders.value[bagianUjianId] = (localOrders.value[bagianUjianId] || []).map((questionId) => {
            if (questionPartId(bagianUjianId, questionId) !== partId) return questionId;

            return orderedQuestionIds[questionIndex++];
        });
    }

    initItems(bagianUjianId);
    updateDirty(bagianUjianId);
}

function cancelArrangement(bagianUjian) {
    initializeSection(bagianUjian.id);
}

function saveArrangement(bagianUjian) {
    const urutan = localOrders.value[bagianUjian.id] || [];
    const soalOrders = urutan.map((soalId, index) => ({
        soal_id: soalId,
        nomor: index + 1,
    }));

    router.patch(route('admin.ujian.bagian.susunan', [props.ujian.id, bagianUjian.id]), {
        urutan_soal: soalOrders,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            initializeSection(bagianUjian.id);
        },
    });
}

watch(activeSkillId, (value) => {
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
        <div>
            <div class="relative">
                <div class="mb-4">
                    <h2 class="text-title-lg font-semibold text-text-heading">Soal Ujian</h2>
                    <p class="mt-1 text-body-md text-text-muted">
                        Pilih keterampilan dan bagian soal. Soal hanya dapat diurutkan di dalam bagian aktif.
                    </p>
                </div>

                <div
                    v-if="skillTabs.length"
                    class="flex min-w-0 items-end gap-1"
                >
                    <div class="min-w-0 flex-1">
                        <BaseTabsChrome
                            :items="skillTabs"
                            :model-value="activeSkillId"
                            aria-label="Keterampilan ujian"
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
                                aria-label="Bagian soal"
                                class="min-w-0 max-w-full"
                                @update:model-value="selectPart"
                            />
                        </div>
                    </div>

                    <QuestionArrangement
                        :ujian="ujian"
                        :bagian-ujian="activeSection"
                        :soal-per-bagian="soalPerBagian"
                        :items="visibleItems"
                        :part-id="activePartId"
                        :part-label="activePartLabel"
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
                Belum ada bagian ujian.
            </div>
        </div>
    </TataLetakDasbor>
</template>
