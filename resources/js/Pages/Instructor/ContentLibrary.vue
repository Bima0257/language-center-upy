<script setup>
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Components/Dashboard/DashboardLayout.vue";
import UploadProgressBar from "@/Components/Shared/UploadProgressBar.vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import ContentSelector from "@/Components/ContentLibrary/ContentSelector.vue";
import ContentFilters from "@/Components/ContentLibrary/ContentFilters.vue";
import QuestionTable from "@/Components/ContentLibrary/QuestionTable.vue";
import PassageGroupCard from "@/Components/ContentLibrary/PassageGroupCard.vue";
import { IconBook, IconPlus, IconCheck, IconX, IconBooks } from "@tabler/icons-vue";
import { ref, computed } from "vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useToast } from "@/Composables/useToast";
import { useUploadProgress } from "@/Composables/useUploadProgress";

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    questions: { type: Object, default: () => ({ data: [] }) },
    examTypes: { type: Array, default: () => [] },
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ["draft", "approved", "rejected"] },
    filters: { type: Object, default: () => ({}) },
});

const canReview = computed(() => {
    const roles = page.props.auth?.roles || [];
    return roles.some((r) => r === "admin" || r === "superadmin");
});

const searchQuery = ref(props.filters.search || "");
const selectedSkillId = ref(props.filters.skill_id || "");
const selectedBankId = ref(props.filters.question_bank_id || "");
const selectedStatus = ref(props.filters.status || "");
const selectedPartId = ref(props.filters.part_id || "");
const selectedTypeId = ref("");

const createParams = computed(() => {
    const p = {};
    if (selectedBankId.value) p.question_bank_id = selectedBankId.value;
    if (selectedSkillId.value) p.skill_id = selectedSkillId.value;
    if (selectedPartId.value) p.part_id = selectedPartId.value;
    if (selectedStatus.value) p.status = selectedStatus.value;
    if (searchQuery.value) p.search = searchQuery.value;
    return p;
});

if (selectedBankId.value) {
    const bank = props.questionBanks.find(
        (b) => String(b.id) === String(selectedBankId.value),
    );
    if (bank?.exam_type_id) {
        selectedTypeId.value = String(bank.exam_type_id);
    }
}

const selectedType = computed(
    () =>
        props.examTypes.find(
            (t) => String(t.id) === String(selectedTypeId.value),
        ) || null,
);

const filteredBanks = computed(() => {
    if (!selectedType.value) return props.questionBanks;
    return props.questionBanks.filter(
        (b) => String(b.exam_type_id) === String(selectedType.value.id),
    );
});

function onTypeChange(value) {
    selectedTypeId.value = value;
    selectedBankId.value = "";
    selectedSkillId.value = "";
    selectedPartId.value = "";
    selectedStatus.value = "";
    searchQuery.value = "";
    applyFilters();
}

function onBankChange(value) {
    selectedBankId.value = value;
    selectedSkillId.value = "";
    selectedPartId.value = "";
    applyFilters();
}

const selectedIds = ref([]);
const expandedPassages = ref({});
const editingPassageId = ref(null);
const passageEditType = ref("text");
const addingPassageId = ref(null);

const passageForm = useForm({
    title: "",
    type: "text",
    content_text: "",
    audio_file: null,
    image_file: null,
});

const quickQuestionForm = useForm({
    passage_id: null,
    question_bank_id: "",
    skill_id: "",
    skill_part_id: "",
    question_text: "",
    option_a: "",
    option_b: "",
    option_c: "",
    option_d: "",
    correct_answer: "",
});

const optionKeys = ["A", "B", "C", "D"];

const quickSelectedBank = computed(
    () =>
        props.questionBanks.find(
            (b) => String(b.id) === String(quickQuestionForm.question_bank_id),
        ) || null,
);

const availableQuickSkills = computed(() => {
    if (!quickSelectedBank.value) return props.skills;
    return props.skills.filter(
        (s) =>
            String(s.exam_type_id) ===
            String(quickSelectedBank.value.exam_type_id),
    );
});

const quickIsListening = computed(() => {
    const s = props.skills.find(
        (s) => String(s.id) === String(quickQuestionForm.skill_id),
    );
    return s?.code === "listening";
});

function quickParts() {
    return props.parts.filter(
        (p) => String(p.skill_id) === String(quickQuestionForm.skill_id),
    );
}

function onQuickSkillChange() {
    quickQuestionForm.skill_part_id = "";
}

const statusLabels = {
    draft: "Draf",
    approved: "Disetujui",
    rejected: "Ditolak",
};

const passageTypeLabels = {
    text: "Teks",
    audio: "Audio",
    image: "Gambar",
    prompt: "Prompt",
};

function skillName(id) {
    return props.skills.find((s) => s.id === id)?.name || "";
}

function bankName(id) {
    return props.questionBanks.find((b) => b.id === id)?.name || "";
}

function passageTypeLabel(type) {
    return passageTypeLabels[type] || type || "Teks";
}

function isPassageExpanded(id) {
    return !!expandedPassages.value[id];
}

function togglePassage(id) {
    expandedPassages.value[id] = !expandedPassages.value[id];
}

function startEditPassage(passage) {
    editingPassageId.value = passage.id;
    passageEditType.value = passage.type || "text";
    passageForm.clearErrors();
    passageForm.reset();
    passageForm.title = passage.title;
    passageForm.type = passage.type || "text";
    passageForm.content_text = passage.content_text || "";
}

function cancelEditPassage() {
    editingPassageId.value = null;
    passageForm.reset();
}

function savePassage(passage) {
    passageForm.put(route("content-library.passages.update", passage.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingPassageId.value = null;
            passageForm.reset();
            router.reload({
                only: ["questions"],
                preserveState: true,
                preserveScroll: true,
            });
        },
    });
}

const { showUploadProgress, uploadLabel } = useUploadProgress(passageForm);

function openQuickAdd(passage) {
    addingPassageId.value = passage.id;
    quickQuestionForm.clearErrors();
    quickQuestionForm.reset();
    quickQuestionForm.passage_id = passage.id;
    if (props.questionBanks.length === 1) {
        quickQuestionForm.question_bank_id = props.questionBanks[0].id;
    }
}

function closeQuickAdd() {
    addingPassageId.value = null;
    quickQuestionForm.reset();
}

function saveQuickQuestion() {
    quickQuestionForm
        .transform((data) => ({
            passage_id: data.passage_id,
            question_bank_id: data.question_bank_id,
            questions: [
                {
                    skill_id: data.skill_id,
                    skill_part_id: data.skill_part_id,
                    question_text: data.question_text,
                    option_a: data.option_a,
                    option_b: data.option_b,
                    option_c: data.option_c,
                    option_d: data.option_d,
                    correct_answer: data.correct_answer,
                },
            ],
        }))
        .post(route("content-library.store"), {
            preserveScroll: true,
            onSuccess: () => {
                addingPassageId.value = null;
                quickQuestionForm.reset();
                router.reload({
                    only: ["questions"],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        });
}

async function deletePassage(passage) {
    if (
        !(await confirm.confirm(
            `Hapus materi soal "${passage.title}"? Soal di dalamnya tidak ikut terhapus.`,
        ))
    )
        return;
    router.delete(route("content-library.passages.destroy", passage.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

const skillGroups = computed(() => {
    const skillMap = new Map();

    for (const q of props.questions.data) {
        const sid = q.skill_id;
        if (!skillMap.has(sid)) {
            skillMap.set(sid, { skill: q.skill || null, parts: new Map() });
        }
        const bucket = skillMap.get(sid);

        const pid = q.skill_part_id || "none";
        if (!bucket.parts.has(pid)) {
            bucket.parts.set(pid, {
                part: q.skillPart || null,
                passageGroups: new Map(),
                standalone: [],
            });
        }
        const partBucket = bucket.parts.get(pid);

        if (q.passage) {
            if (!partBucket.passageGroups.has(q.passage_id)) {
                partBucket.passageGroups.set(q.passage_id, {
                    passage: q.passage,
                    questions: [],
                });
            }
            partBucket.passageGroups.get(q.passage_id).questions.push(q);
        } else {
            partBucket.standalone.push(q);
        }
    }

    return Array.from(skillMap.values())
        .map((s) => ({
            skill: s.skill,
            parts: Array.from(s.parts.values())
                .map((p) => ({
                    part: p.part,
                    groups: Array.from(p.passageGroups.values()),
                    standalone: p.standalone,
                }))
                .sort((a, b) => (a.part?.order ?? 99) - (b.part?.order ?? 99)),
        }))
        .sort((a, b) =>
            (a.skill?.name || "").localeCompare(b.skill?.name || ""),
        );
});

function partGroupTotal(partGroup) {
    return (
        partGroup.groups.reduce((n, g) => n + g.questions.length, 0) +
        partGroup.standalone.length
    );
}

function skillGroupTotal(skillGroup) {
    return skillGroup.parts.reduce((n, p) => n + partGroupTotal(p), 0);
}

let debounceTimer = null;

function applyFilters() {
    const p = {};
    if (selectedSkillId.value) p.skill_id = selectedSkillId.value;
    if (selectedBankId.value) p.question_bank_id = selectedBankId.value;
    if (selectedStatus.value) p.status = selectedStatus.value;
    if (selectedPartId.value) p.part_id = selectedPartId.value;
    if (searchQuery.value) p.search = searchQuery.value;
    router.get(route("content-library.index"), p, { preserveState: true });
}

function onSearchInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
}
function resetFilters() {
    searchQuery.value = "";
    selectedSkillId.value = "";
    selectedBankId.value = "";
    selectedStatus.value = "";
    selectedPartId.value = "";
    applyFilters();
}

async function deleteQuestion(id, text) {
    if (!(await confirm.confirm(`Hapus soal: "${text.substring(0, 50)}..."?`)))
        return;
    router.delete(route("content-library.destroy", id), {
        preserveScroll: true,
    });
}

async function reviewQuestion(id, status) {
    const note =
        status === "rejected"
            ? await confirm.prompt("Catatan penolakan (opsional):")
            : null;
    router.patch(
        route("content-library.review", id),
        { status, review_note: note },
        { preserveScroll: true },
    );
}

function isPassageSelected(group) {
    return (
        group.questions.length > 0 &&
        group.questions.every((q) => selectedIds.value.includes(q.id))
    );
}

function togglePassageSelection(group) {
    const ids = group.questions.map((q) => q.id);
    if (isPassageSelected(group)) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
    } else {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...ids]));
    }
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
}

async function bulkReview(status) {
    if (selectedIds.value.length === 0) {
        toast.warning("Pilih soal terlebih dahulu.");
        return;
    }
    const note =
        status === "rejected"
            ? await confirm.prompt("Catatan penolakan (opsional):")
            : null;
    router.patch(
        route("content-library.bulk-review"),
        { ids: selectedIds.value, status, review_note: note },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
        },
    );
}
</script>

<template>
    <Head title="Bank Soal" />
    <DashboardLayout title="Bank Soal">
        <ContentSelector
            :exam-types="examTypes"
            :filtered-banks="filteredBanks"
            :selected-type-id="selectedTypeId"
            :selected-bank-id="selectedBankId"
            :bank-disabled="!selectedTypeId"
            :reset-disabled="!selectedBankId"
            @type-change="onTypeChange"
            @bank-change="onBankChange"
            @reset="resetFilters"
        />

        <template v-if="selectedBankId">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <p class="text-text-body text-text-body text-body-md">
                        {{ questions.total || 0 }} soal ditemukan
                    </p>
                    <Link
                        :href="route('content-library.passages.index')"
                        class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline"
                    >
                        <IconBooks :size="16" /> Kelola Materi Soal
                    </Link>
                    <Link
                        v-if="canReview"
                        :href="route('content-library.question-banks.index')"
                        class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline"
                    >
                        <IconBooks :size="16" /> Kelola Bank Soal
                    </Link>
                </div>
                <Link
                    :href="route('content-library.create', createParams)"
                    class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95"
                >
                    <IconPlus :size="18" /> Tambah Soal Baru
                </Link>
            </div>

            <ContentFilters
                :search-query="searchQuery"
                :selected-skill-id="selectedSkillId"
                :selected-part-id="selectedPartId"
                :selected-status="selectedStatus"
                :skills="skills"
                :parts="parts"
                :statuses="statuses"
                :status-labels="statusLabels"
                @update:searchQuery="searchQuery = $event"
                @search="onSearchInput"
                @update:selectedSkillId="selectedSkillId = $event"
                @update:selectedPartId="selectedPartId = $event"
                @update:selectedStatus="selectedStatus = $event"
                @filter-change="applyFilters"
            />

            <EmptyState
                v-if="questions.data?.length === 0"
                :icon="IconBook"
                title="Tidak ada soal ditemukan."
            />

            <div v-else class="space-y-8">
                <template
                    v-for="skillGroup in skillGroups"
                    :key="skillGroup.skill?.id || 'no-skill'"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-block bg-primary-container text-white px-3.5 py-1.5 rounded-full text-label-md font-semibold"
                            >{{ skillGroup.skill?.name || "Tanpa Skill" }}</span
                        >
                        <span class="text-label-md text-text-muted"
                            >{{ skillGroupTotal(skillGroup) }} soal</span
                        >
                    </div>

                    <div class="space-y-6">
                        <template
                            v-for="partGroup in skillGroup.parts"
                            :key="partGroup.part?.id || 'no-part'"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-block bg-pastel-purple/30 text-primary px-3.5 py-1.5 rounded-full text-label-md font-semibold"
                                    >{{
                                        partGroup.part?.name || "Tanpa Part"
                                    }}</span
                                >
                                <span class="text-label-md text-text-muted"
                                    >{{ partGroupTotal(partGroup) }} soal</span
                                >
                            </div>

                            <div class="space-y-4">
                                <PassageGroupCard
                                    v-for="group in partGroup.groups"
                                    :key="group.passage.id"
                                    :group="group"
                                    :can-review="canReview"
                                    :passage-form="passageForm"
                                    :editing-passage-id="editingPassageId"
                                    :passage-edit-type="passageEditType"
                                    :selected-ids="selectedIds"
                                    :skill-name-fn="skillName"
                                    :bank-name-fn="bankName"
                                    :option-keys="optionKeys"
                                    :quick-form="quickQuestionForm"
                                    :question-banks="questionBanks"
                                    :adding-passage-id="addingPassageId"
                                    :quick-skills="availableQuickSkills"
                                    :quick-parts-fn="quickParts"
                                    :quick-is-listening="quickIsListening"
                                    :is-passage-selected-fn="isPassageSelected"
                                    :toggle-passage-selection-fn="togglePassageSelection"
                                    :toggle-passage-fn="togglePassage"
                                    :passage-type-label-fn="passageTypeLabel"
                                    :is-passage-expanded-fn="isPassageExpanded"
                                    @start-edit="startEditPassage"
                                    @cancel-edit="cancelEditPassage"
                                    @save-passage="savePassage"
                                    @add-soal="openQuickAdd"
                                    @close-add="closeQuickAdd"
                                    @save-quick="saveQuickQuestion"
                                    @quick-skill-change="onQuickSkillChange"
                                    @question-toggle="toggleSelect"
                                    @question-review="reviewQuestion"
                                    @question-delete="deleteQuestion"
                                    @delete-passage="deletePassage"
                                />

                                <div v-if="partGroup.standalone.length">
                                    <div
                                        class="flex items-center gap-2 px-1 mb-3"
                                    >
                                        <IconBook
                                            :size="16"
                                            class="text-text-muted"
                                        />
                                        <p
                                            class="text-label-md font-semibold text-text-muted uppercase tracking-wider"
                                        >
                                            Soal Standalone
                                        </p>
                                    </div>
                                    <div
                                        class="bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden"
                                    >
                                        <QuestionTable
                                            :questions="partGroup.standalone"
                                            :can-review="canReview"
                                            :skill-name-fn="skillName"
                                            :bank-name-fn="bankName"
                                            :selected-ids="selectedIds"
                                            @toggle="toggleSelect"
                                            @review="reviewQuestion"
                                            @delete="deleteQuestion"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <Pagination
                :links="questions.links"
                :total="questions.total"
                :per-page="questions.per_page"
            />
            <UploadProgressBar
                :show="showUploadProgress"
                :label="uploadLabel"
            />

            <Transition name="fade-slide">
                <div
                    v-if="canReview && selectedIds.length > 0"
                    class="fixed left-1/2 -translate-x-1/2 bottom-[76px] md:bottom-6 z-40 bg-surface-white rounded-full shadow-lg border border-outline-variant/30 px-4 py-3 flex items-center gap-3"
                >
                    <span
                        class="text-label-md font-semibold text-primary whitespace-nowrap"
                        >{{ selectedIds.length }} soal dipilih</span
                    >
                    <span class="w-px h-5 bg-outline-variant/40"></span>
                    <button
                        @click="bulkReview('approved')"
                        class="flex items-center gap-1 px-4 py-2 bg-green-600 text-white rounded-full text-label-md font-medium hover:bg-green-700 transition-all"
                    >
                        <IconCheck :size="16" />Setujui
                    </button>
                    <button
                        @click="bulkReview('rejected')"
                        class="flex items-center gap-1 px-4 py-2 bg-error-red text-white rounded-full text-label-md font-medium hover:bg-red-700 transition-all"
                    >
                        <IconX :size="16" />Tolak
                    </button>
                    <button
                        @click="selectedIds = []"
                        class="px-3 py-2 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all"
                    >
                        Batal
                    </button>
                </div>
            </Transition>
        </template>

        <!-- EMPTY STATE: BELUM PILIH BANK -->
        <EmptyState
            v-else
            :icon="IconBook"
            title="Pilih Jenis Tes dan Bank Soal"
            description="Daftar soal akan muncul setelah kamu memilih bank soal."
        />
    </DashboardLayout>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translate(-50%, 10px);
}
</style>
