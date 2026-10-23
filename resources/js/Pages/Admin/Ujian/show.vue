<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { ref } from 'vue';
import AddQuestionsModal from './components/AddQuestionsModal.vue';
import ExamSummary from './components/ExamSummary.vue';
import QuestionArrangement from './components/QuestionArrangement.vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    bagianSoal: { type: Array, default: () => [] },
    soalPerBagian: { type: Object, default: () => ({}) },
    soalDapatDilampirkan: { type: Object, default: () => ({}) },
});

const expandedSections = ref({});
const localOrders = ref({});
const itemsBySection = ref({});
const dirtySections = ref({});

// ===== Modal Tambah Soal =====
const showQuestionModal = ref(false);
const activeSection = ref(null);
const soalForm = useForm({ soal_ids: [] });

function openAddQuestions(bagianUjian) {
    activeSection.value = bagianUjian;
    soalForm.clearErrors();
    soalForm.reset();
    showQuestionModal.value = true;
}

function attachableFor(bagianUjianId) {
    return props.soalDapatDilampirkan[bagianUjianId] || [];
}

function toggleQuestion(soalId) {
    const idx = soalForm.soal_ids.indexOf(soalId);
    if (idx >= 0) {
        soalForm.soal_ids.splice(idx, 1);
    } else {
        soalForm.soal_ids.push(soalId);
    }
}

function submitQuestions() {
    if (soalForm.soal_ids.length === 0) return;
    soalForm.post(route('admin.ujian.bagian.soal.simpan', [props.ujian.id, activeSection.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showQuestionModal.value = false;
            soalForm.reset();
        },
    });
}

// ===== Susunan (drag & drop) =====
function savedOrderFor(bagianUjianId) {
    return (props.soalPerBagian[bagianUjianId] || [])
        .slice()
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0))
        .map(r => r.soal_id);
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
    for (const row of (props.soalPerBagian[bagianUjianId] || [])) {
        byId[row.soal_id] = row;
    }

    const items = [];
    let currentPartKey = null;
    let currentPassageKey = null;

    for (const qid of urutan) {
        const row = byId[qid];
        if (!row) continue;

        const bagianSoalId = row.soal?.bagian_soal_id ? String(row.soal.bagian_soal_id) : 'none';
        if (bagianSoalId !== currentPartKey) {
            currentPartKey = bagianSoalId;
            currentPassageKey = null;
            const bagianSoal = bagianSoalId === 'none' ? null : props.bagianSoal.find(p => String(p.id) === bagianSoalId);
            items.push({
                type: 'bagianSoal',
                key: bagianSoalId === 'none' ? 'bagianSoal-none' : `bagianSoal-${bagianSoalId}`,
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
                    materiSoal: row.soal.materi_soal,
                    soalPertamaId: qid,
                    jumlahSoal: 0,
                });
            }
        }

        items.push({ type: 'soal', key: `q-${qid}`, soalId: qid, row });
    }

    const counts = {};
    for (const it of items) {
        if (it.type !== 'soal') continue;
        const pid = it.row.soal?.bagian_soal_id ? String(it.row.soal.bagian_soal_id) : 'none';
        counts[pid] = (counts[pid] || 0) + 1;
    }
    for (const it of items) {
        if (it.type === 'bagianSoal') {
            it.jumlahSoal = counts[it.key.replace('bagianSoal-', '')] || 0;
        }
        if (it.type === 'materiSoal') {
            const pid = it.key.replace('pass-', '');
            const qids = urutan.filter(qid => {
                const r = byId[qid];
                return r && String(r.soal?.materi_soal_id) === pid;
            });
            it.jumlahSoal = qids.length;
        }
    }

    return items;
}

function initItems(bagianUjianId) {
    itemsBySection.value[bagianUjianId] = buildItems(bagianUjianId);
}

function toggleSection(id) {
    expandedSections.value[id] = !expandedSections.value[id];
    if (expandedSections.value[id]) {
        initLocalOrders(id);
        initItems(id);
        dirtySections.value[id] = false;
    }
}

function onSectionSort(bagianUjianId) {
    const items = itemsBySection.value[bagianUjianId] || [];
    localOrders.value[bagianUjianId] = items
        .filter(it => it.type === 'soal')
        .map(it => it.soalId);
    initItems(bagianUjianId);
    updateDirty(bagianUjianId);
}

function cancelArrangement(bagianUjian) {
    initLocalOrders(bagianUjian.id);
    initItems(bagianUjian.id);
    dirtySections.value[bagianUjian.id] = false;
}

function saveArrangement(bagianUjian) {
    const urutan = localOrders.value[bagianUjian.id] || [];
    const soalOrders = urutan.map((soalId, index) => ({
        soal_id: soalId,
        urutan: index + 1,
    }));

    router.patch(route('admin.ujian.bagian.susunan', [props.ujian.id, bagianUjian.id]), {
        urutan_soal: soalOrders,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            initLocalOrders(bagianUjian.id);
            initItems(bagianUjian.id);
            dirtySections.value[bagianUjian.id] = false;
        },
    });
}
function syncSections() {
    router.post(route('admin.ujian.sinkronkan-bagian', props.ujian.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="ujian.judul" />
    <TataLetakDasbor :title="ujian.judul" :subtitle="(ujian.mode === 'tryout' ? 'Try Out' : 'Ujian Resmi') + ' • ' + ujian.durasi_menit + ' menit'">
        <div class="space-y-6">
            <ExamSummary :ujian="ujian" :edit-href="route('admin.ujian.ubah', ujian.id)" />
            <QuestionArrangement
                :ujian="ujian"
                :soal-per-bagian="soalPerBagian"
                :soal-dapat-dilampirkan="soalDapatDilampirkan"
                :expanded-sections="expandedSections"
                :items-by-section="itemsBySection"
                :dirty-sections="dirtySections"
                @sync-sections="syncSections"
                @toggle-section="toggleSection"
                @add-questions="openAddQuestions"
                @sort="onSectionSort"
                @save-arrangement="saveArrangement"
                @cancel-arrangement="cancelArrangement"
            />
        </div>

        <AddQuestionsModal
            :show="showQuestionModal"
            :active-section="activeSection"
            :questions="activeSection ? attachableFor(activeSection.id) : []"
            :form="soalForm"
            @close="showQuestionModal = false"
            @toggle-question="toggleQuestion"
            @submit="submitQuestions"
        />
    </TataLetakDasbor>
</template>
