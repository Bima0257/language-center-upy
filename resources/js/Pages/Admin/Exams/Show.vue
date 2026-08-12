<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import draggable from 'vuedraggable';
import { IconEdit, IconChevronDown, IconChevronRight, IconFileDescription, IconCheck, IconGripVertical } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const toast = useToast();

const props = defineProps({
    exam: { type: Object, required: true },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    sectionQuestions: { type: Object, default: () => ({}) },
});

const expandedSections = ref({});
const localOrders = ref({});
const itemsBySection = ref({});
const dirtySections = ref({});

function savedOrderFor(sectionId) {
    return (props.sectionQuestions[sectionId] || [])
        .slice()
        .sort((a, b) => (a.order || 0) - (b.order || 0))
        .map(r => r.question_id);
}

function updateDirty(sectionId) {
    const current = localOrders.value[sectionId] || [];
    const saved = savedOrderFor(sectionId);
    dirtySections.value[sectionId] = JSON.stringify(current) !== JSON.stringify(saved);
}

function initLocalOrders(sectionId) {
    localOrders.value[sectionId] = savedOrderFor(sectionId);
}

function buildItems(sectionId) {
    const order = localOrders.value[sectionId] || [];
    const byId = {};
    for (const row of (props.sectionQuestions[sectionId] || [])) {
        byId[row.question_id] = row;
    }

    const items = [];
    let currentPartKey = null;
    let currentPassageKey = null;

    for (const qid of order) {
        const row = byId[qid];
        if (!row) continue;

        const partId = row.question?.skill_part_id ? String(row.question.skill_part_id) : 'none';
        if (partId !== currentPartKey) {
            currentPartKey = partId;
            currentPassageKey = null;
            const part = partId === 'none' ? null : props.parts.find(p => p.id === Number(partId));
            items.push({
                type: 'part',
                key: partId === 'none' ? 'part-none' : `part-${partId}`,
                name: part?.name || (partId === 'none' ? 'Tanpa Part' : 'Part'),
                count: 0,
            });
        }

        const passageId = row.question?.passage_id ? String(row.question.passage_id) : null;
        const passageKey = passageId ? `pass-${passageId}` : null;

        if (passageKey !== currentPassageKey) {
            currentPassageKey = passageKey;
            if (passageKey) {
                items.push({
                    type: 'passage',
                    key: passageKey,
                    passage: row.question.passage,
                    firstQuestionId: qid,
                    count: 0,
                });
            }
        }

        items.push({ type: 'question', key: `q-${qid}`, questionId: qid, row });
    }

    const counts = {};
    for (const it of items) {
        if (it.type !== 'question') continue;
        const pid = it.row.question?.skill_part_id ? String(it.row.question.skill_part_id) : 'none';
        counts[pid] = (counts[pid] || 0) + 1;
    }
    for (const it of items) {
        if (it.type === 'part') {
            it.count = counts[it.key.replace('part-', '')] || 0;
        }
        if (it.type === 'passage') {
            const pid = it.key.replace('pass-', '');
            const qids = order.filter(qid => {
                const r = byId[qid];
                return r && String(r.question?.passage_id) === pid;
            });
            it.count = qids.length;
        }
    }

    return items;
}

function initItems(sectionId) {
    itemsBySection.value[sectionId] = buildItems(sectionId);
}

function toggleSection(id) {
    expandedSections.value[id] = !expandedSections.value[id];
    if (expandedSections.value[id]) {
        initLocalOrders(id);
        initItems(id);
        dirtySections.value[id] = false;
    }
}

function onSectionSort(sectionId) {
    const items = itemsBySection.value[sectionId] || [];
    localOrders.value[sectionId] = items
        .filter(it => it.type === 'question')
        .map(it => it.questionId);
    initItems(sectionId);
    updateDirty(sectionId);
}

function cancelArrangement(section) {
    initLocalOrders(section.id);
    initItems(section.id);
    dirtySections.value[section.id] = false;
}

function skillName(id) {
    return props.skills.find(s => s.id === id)?.name || '';
}

function partName(id) {
    return props.parts.find(p => p.id === id)?.name || '';
}

function sectionOffset(section) {
    let offset = 0;
    for (const s of props.exam.sections) {
        if (s.id === section.id) break;
        offset += (props.sectionQuestions[s.id] || []).length;
    }
    return offset;
}

function positionOf(section, questionId) {
    const order = localOrders.value[section.id] || [];
    return order.indexOf(questionId) + 1 + sectionOffset(section);
}

function saveArrangement(section) {
    const order = localOrders.value[section.id] || [];
    const questionOrders = order.map((questionId, index) => ({
        question_id: questionId,
        number: index + 1,
    }));

    router.patch(route('admin.exams.sections.arrangement', [props.exam.id, section.id]), {
        question_orders: questionOrders,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Susunan section disimpan.');
            initLocalOrders(section.id);
            initItems(section.id);
            dirtySections.value[section.id] = false;
        },
    });
}

function questionPreview(q) {
    return (q.question_text || '').substring(0, 90);
}
</script>

<template>
    <Head :title="exam.title" />
    <DashboardLayout :title="exam.title">
        <div class="space-y-6">
            <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-block bg-pastel-blue/50 text-primary px-3 py-1 rounded-full text-label-md font-medium">
                            {{ exam.mode === 'tryout' ? 'Try Out' : 'Ujian Resmi' }}
                        </span>
                        <span class="text-text-muted text-label-md">{{ exam.duration_minutes }} menit</span>
                    </div>
                    <Link :href="route('admin.exams.edit', exam.id)"
                          class="flex items-center gap-2 text-secondary text-label-md font-medium hover:underline">
                        <IconEdit :size="16" /> Edit
                    </Link>
                </div>
                <p v-if="exam.description" class="text-text-body text-text-body text-body-md">{{ exam.description }}</p>
            </div>

            <BaseCard>
                <h2 class="text-title-lg font-semibold text-primary mb-4">Sections &amp; Soal</h2>

                <div v-if="exam.sections?.length" class="space-y-4">
                    <div v-for="section in exam.sections" :key="section.id"
                         class="border border-outline-variant/50 rounded-2xl overflow-hidden">
                        <div @click="toggleSection(section.id)"
                             class="flex items-center justify-between p-4 bg-surface-container-low cursor-pointer hover:bg-surface-container transition-colors">
                            <div class="flex items-center gap-3">
                                <button class="text-text-muted">
                                    <IconChevronDown v-if="expandedSections[section.id]" :size="18" />
                                    <IconChevronRight v-else :size="18" />
                                </button>
                                <IconFileDescription :size="18" class="text-secondary" />
                                <div>
                                    <p class="font-semibold text-primary">{{ section.title }}</p>
                                    <p class="text-text-muted text-label-md">
                                        {{ skillName(section.skill_id) }} — {{ (sectionQuestions[section.id] || []).length }} soal terpasang
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="expandedSections[section.id]" class="p-4 border-t border-outline-variant/30 space-y-5">
                            <!-- SORT ORDER: DRAG & DROP -->
                            <p v-if="(itemsBySection[section.id] || []).length === 0" class="text-label-md text-text-muted py-2">
                                Belum ada soal di section ini.
                            </p>
                            <draggable
                                v-model="itemsBySection[section.id]"
                                item-key="key"
                                :filter="'.part-header,.passage-header'"
                                ghost-class="opacity-40"
                                class="space-y-2"
                                @sort="onSectionSort(section.id)"
                            >
                                <template #item="{ element }">
                                    <div :class="element.type === 'part'
                                        ? 'part-header select-none'
                                        : element.type === 'passage'
                                            ? 'passage-header select-none'
                                            : 'flex items-center gap-3 bg-surface-white border border-outline-variant/40 rounded-xl px-3 py-2 cursor-grab active:cursor-grabbing hover:border-secondary/50 transition-colors'">
                                        <!-- PART HEADER (non-draggable separator) -->
                                        <div v-if="element.type === 'part'" class="flex items-center gap-2 pt-1">
                                            <span class="inline-block bg-pastel-purple/40 text-primary px-3 py-1 rounded-full text-label-md font-semibold">{{ element.name }}</span>
                                            <span class="text-label-md text-text-muted">{{ element.count }} soal</span>
                                        </div>
                                        <!-- PASSAGE SUB-HEADER (non-draggable) -->
                                        <div v-else-if="element.type === 'passage'"
                                             class="bg-surface-container-low rounded-xl px-3 py-2 border border-outline-variant/30">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-body-md font-semibold text-primary flex items-center gap-2 min-w-0">
                                                    📄 <span class="truncate">{{ element.passage?.title || 'Materi' }}</span>
                                                    <span class="text-label-md text-text-muted shrink-0">— {{ element.count }} soal</span>
                                                </span>
                                                <Link :href="route('content-library.preview', element.firstQuestionId)"
                                                      class="text-secondary text-label-md font-medium hover:underline shrink-0">
                                                    Lihat Detail
                                                </Link>
                                            </div>
                                        </div>
                                        <!-- QUESTION ROW (draggable) -->
                                        <div v-else class="flex items-center gap-3 w-full">
                                            <IconGripVertical :size="16" class="text-text-muted shrink-0" />
                                            <span class="w-8 text-center font-bold text-primary text-body-md shrink-0">{{ positionOf(section, element.questionId) }}</span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-body-md text-primary font-medium truncate">{{ questionPreview(element.row.question) }}</p>
                                                <p v-if="element.row.question.passage" class="text-label-md text-text-muted truncate">📄 {{ element.row.question.passage.title }}</p>
                                            </div>
                                            <span v-if="element.row.question.audio_url || element.row.question.passage?.audio_url"
                                                  class="text-label-md text-text-muted shrink-0">🎧</span>
                                        </div>
                                    </div>
                                </template>
                            </draggable>

                            <div v-if="dirtySections[section.id]" class="flex flex-wrap items-center gap-3 pt-2">
                                <BaseButton size="sm" @click="saveArrangement(section)">
                                    <IconCheck :size="16" /> Simpan Susunan
                                </BaseButton>
                                <BaseButton variant="secondary" size="sm" @click="cancelArrangement(section)">
                                    Batal
                                </BaseButton>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10">
                    <p class="text-text-muted text-text-body text-body-md">Belum ada section.</p>
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
