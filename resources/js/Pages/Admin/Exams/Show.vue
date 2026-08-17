<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import draggable from 'vuedraggable';
import { IconEdit, IconChevronDown, IconChevronRight, IconFileDescription, IconCheck, IconGripVertical, IconPlus, IconX, IconTrash } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import { skillLabel } from '@/constants/skills';

const props = defineProps({
    exam: { type: Object, required: true },
    skillOptions: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    questionBanks: { type: Array, default: () => [] },
    sectionQuestions: { type: Object, default: () => ({}) },
    attachableQuestions: { type: Object, default: () => ({}) },
});

const expandedSections = ref({});
const localOrders = ref({});
const itemsBySection = ref({});
const dirtySections = ref({});

// ===== Modal Tambah Section =====
const showSectionModal = ref(false);
const sectionForm = useForm({
    question_bank_id: '',
    skill: '',
    title: '',
    order: 1,
});

const sectionTitlePreview = computed(() => {
    if (!sectionForm.skill) return '';
    const skill = skillLabel(sectionForm.skill) || sectionForm.skill;
    const bank = props.questionBanks.find((b) => String(b.id) === String(sectionForm.question_bank_id))?.name;
    return bank ? `${skill} — ${bank}` : skill;
});

watch([() => sectionForm.question_bank_id, () => sectionForm.skill], () => {
    sectionForm.title = sectionTitlePreview.value;
});

function openAddSection() {
    sectionForm.clearErrors();
    sectionForm.reset();
    sectionForm.order = (props.exam.sections?.length || 0) + 1;
    showSectionModal.value = true;
}

function submitSection() {
    sectionForm.post(route('admin.exams.sections.store', props.exam.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSectionModal.value = false;
        },
    });
}

// ===== Modal Tambah Soal =====
const showQuestionModal = ref(false);
const activeSection = ref(null);
const questionForm = useForm({ question_ids: [] });

function openAddQuestions(section) {
    activeSection.value = section;
    questionForm.clearErrors();
    questionForm.reset();
    showQuestionModal.value = true;
}

function attachableFor(sectionId) {
    return props.attachableQuestions[sectionId] || [];
}

function toggleQuestion(questionId) {
    const idx = questionForm.question_ids.indexOf(questionId);
    if (idx >= 0) {
        questionForm.question_ids.splice(idx, 1);
    } else {
        questionForm.question_ids.push(questionId);
    }
}

function submitQuestions() {
    if (questionForm.question_ids.length === 0) return;
    questionForm.post(route('admin.exams.sections.questions.store', [props.exam.id, activeSection.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showQuestionModal.value = false;
            questionForm.reset();
        },
    });
}

function detachQuestion(section, questionId) {
    router.delete(route('admin.exams.sections.questions.destroy', [props.exam.id, section.id, questionId]), {
        preserveScroll: true,
    });
}

// ===== Susunan (drag & drop) =====
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

function skillName(skill) {
    return skillLabel(skill);
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
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-title-lg font-semibold text-primary">Sections &amp; Soal</h2>
                    <BaseButton size="sm" @click="openAddSection">
                        <IconPlus :size="16" /> Tambah Section
                    </BaseButton>
                </div>

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
                                        {{ skillName(section.skill) }}<span v-if="section.question_bank"> — {{ section.question_bank.name }}</span> — {{ (sectionQuestions[section.id] || []).length }} soal terpasang
                                    </p>
                                </div>
                            </div>
                            <button
                                v-if="attachableFor(section.id).length > 0"
                                @click.stop="openAddQuestions(section)"
                                class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                                <IconPlus :size="16" /> Tambah Soal ({{ attachableFor(section.id).length }})
                            </button>
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
                                            <span v-if="element.row.question.passage?.audio_url"
                                                  class="text-label-md text-text-muted shrink-0">🎧</span>
                                            <button @click="detachQuestion(section, element.questionId)"
                                                    class="p-1.5 text-text-muted hover:text-error-red transition-colors shrink-0" title="Lepas dari section">
                                                <IconTrash :size="16" />
                                            </button>
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

        <!-- Modal Tambah Section -->
        <BaseModal :show="showSectionModal" @close="showSectionModal = false" max-width="2xl">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">Tambah Section</h2>
                    <button @click="showSectionModal = false" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="submitSection" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <DropDown
                                v-model="sectionForm.question_bank_id"
                                :options="questionBanks"
                                label="Bank Soal *"
                                placeholder="Pilih bank"
                                option-label="name"
                                option-value="id"
                            />
                            <p v-if="sectionForm.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ sectionForm.errors.question_bank_id }}</p>
                        </div>
                        <div>
                            <DropDown
                                v-model="sectionForm.skill"
                                :options="skillOptions"
                                label="Skill *"
                                placeholder="Pilih skill"
                                option-label="label"
                                option-value="value"
                            />
                            <p v-if="sectionForm.errors.skill" class="text-error-red text-xs mt-1">{{ sectionForm.errors.skill }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Judul Section</label>
                        <input type="text" v-model="sectionForm.title" placeholder="Judul otomatis dari bank & skill"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="sectionForm.errors.title" class="text-error-red text-xs mt-1">{{ sectionForm.errors.title }}</p>
                    </div>
                    <p v-if="sectionTitlePreview" class="text-label-md text-text-muted">
                        Preview: <span class="font-semibold text-primary">{{ sectionTitlePreview }}</span>
                    </p>
                    <div class="flex gap-4 pt-2">
                        <BaseButton type="submit" :disabled="sectionForm.processing" size="xl" class="flex-1">
                            {{ sectionForm.processing ? 'Menyimpan...' : 'Buat Section' }}
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" size="lg" @click="showSectionModal = false">
                            Batal
                        </BaseButton>
                    </div>
                </form>
            </div>
        </BaseModal>

        <!-- Modal Tambah Soal -->
        <BaseModal :show="showQuestionModal" @close="showQuestionModal = false" max-width="2xl" scrollable>
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">Tambah Soal ke Section</h2>
                    <button @click="showQuestionModal = false" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <p v-if="activeSection" class="text-label-md text-text-muted mb-4">
                    Section: <span class="font-semibold text-primary">{{ activeSection.title }}</span> — hanya soal approved dari bank section yang ditampilkan.
                </p>

                <div v-if="activeSection && attachableFor(activeSection.id).length === 0"
                     class="text-center py-8 bg-surface-container-low rounded-2xl">
                    <p class="text-text-muted text-body-md">Tidak ada soal approved yang tersedia untuk ditambahkan.</p>
                </div>

                <div v-else class="space-y-2 max-h-[50vh] overflow-y-auto pr-1">
                    <label v-for="q in attachableFor(activeSection?.id)" :key="q.id"
                           class="flex items-start gap-3 p-3 rounded-xl border border-outline-variant/40 cursor-pointer hover:border-secondary/50 transition-colors">
                        <input type="checkbox"
                               :checked="questionForm.question_ids.includes(q.id)"
                               @change="toggleQuestion(q.id)"
                               class="mt-1 w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <div class="min-w-0">
                            <p class="text-body-md text-primary font-medium truncate">{{ questionPreview(q) }}</p>
                            <p class="text-label-md text-text-muted truncate">
                                {{ q.skillPart?.name || 'Tanpa part' }}<span v-if="q.passage"> — 📄 {{ q.passage.title }}</span>
                            </p>
                        </div>
                    </label>
                </div>

                <div class="flex gap-4 pt-4">
                    <BaseButton type="button" :disabled="questionForm.processing || questionForm.question_ids.length === 0"
                                size="xl" class="flex-1" @click="submitQuestions">
                        {{ questionForm.processing ? 'Menyimpan...' : `Tambah ${questionForm.question_ids.length || ''} Soal` }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="showQuestionModal = false">
                        Batal
                    </BaseButton>
                </div>
            </div>
        </BaseModal>
    </DashboardLayout>
</template>
