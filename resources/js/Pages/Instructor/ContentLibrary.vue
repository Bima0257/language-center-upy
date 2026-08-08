<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import { IconSearch, IconBook, IconPlus, IconCheck, IconX, IconBooks, IconFileDescription, IconEdit, IconUpload, IconTrash, IconHeadphones, IconEye } from '@tabler/icons-vue';
import { ref, computed } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    questions: { type: Object, default: () => ({ data: [] }) },
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    passages: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ['draft', 'approved', 'rejected'] },
    selectedPassage: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const canReview = computed(() => {
    const roles = page.props.auth?.roles || [];
    return roles.some(r => r === 'admin' || r === 'superadmin');
});

const searchQuery = ref(props.filters.search || '');
const selectedSkillId = ref(props.filters.skill_id || '');
const selectedBankId = ref(props.filters.question_bank_id || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPassageId = ref(props.filters.passage_id || '');
const selectedPartId = ref(props.filters.part_id || '');
const selectedIds = ref([]);
const expandedPassages = ref({});
const editingPassageId = ref(null);
const passageEditType = ref('text');
const selectedAudioFile = ref(null);
const selectedImageFile = ref(null);
const addingPassageId = ref(null);

const passageForm = useForm({
    title: '',
    type: 'text',
    content_text: '',
    audio_file: null,
    image_file: null,
});

const quickQuestionForm = useForm({
    passage_id: null,
    question_bank_id: '',
    skill_id: '',
    skill_part_id: '',
    question_text: '',
    option_a: '',
    option_b: '',
    option_c: '',
    option_d: '',
    correct_answer: '',
});

const optionKeys = ['A', 'B', 'C', 'D'];

const quickSelectedBank = computed(() =>
    props.questionBanks.find(b => String(b.id) === String(quickQuestionForm.question_bank_id)) || null,
);

const availableQuickSkills = computed(() => {
    if (!quickSelectedBank.value) return props.skills;
    return props.skills.filter(s => String(s.exam_type_id) === String(quickSelectedBank.value.exam_type_id));
});

const quickIsListening = computed(() => {
    const s = props.skills.find(s => String(s.id) === String(quickQuestionForm.skill_id));
    return s?.code === 'listening';
});

function quickParts() {
    return props.parts.filter(p => String(p.skill_id) === String(quickQuestionForm.skill_id));
}

function onQuickSkillChange() {
    quickQuestionForm.skill_part_id = '';
}

const statusLabels = { draft: 'Draf', approved: 'Disetujui', rejected: 'Ditolak' };
const statusColors = { draft: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300', approved: 'bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300', rejected: 'bg-error-red/10 text-error-red' };

const passageTypeLabels = { text: 'Teks', audio: 'Audio', image: 'Gambar', prompt: 'Prompt' };

function skillName(id) {
    return props.skills.find(s => s.id === id)?.name || '';
}

function bankName(id) {
    return props.questionBanks.find(b => b.id === id)?.name || '';
}

function passageTypeLabel(type) {
    return passageTypeLabels[type] || type || 'Teks';
}

function isPassageExpanded(id) {
    return !!expandedPassages.value[id];
}

function togglePassage(id) {
    expandedPassages.value[id] = !expandedPassages.value[id];
}

function startEditPassage(passage) {
    editingPassageId.value = passage.id;
    passageEditType.value = passage.type || 'text';
    selectedAudioFile.value = null;
    selectedImageFile.value = null;
    passageForm.clearErrors();
    passageForm.reset();
    passageForm.title = passage.title;
    passageForm.type = passage.type || 'text';
    passageForm.content_text = passage.content_text || '';
}

function cancelEditPassage() {
    editingPassageId.value = null;
    passageForm.reset();
    selectedAudioFile.value = null;
    selectedImageFile.value = null;
}

function onEditAudioSelect(e) {
    selectedAudioFile.value = e.target.files[0] || null;
    passageForm.audio_file = selectedAudioFile.value;
    e.target.value = '';
}

function onEditImageSelect(e) {
    selectedImageFile.value = e.target.files[0] || null;
    passageForm.image_file = selectedImageFile.value;
    e.target.value = '';
}

function savePassage(passage) {
    passageForm.put(route('content-library.passages.update', passage.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingPassageId.value = null;
            passageForm.reset();
            selectedAudioFile.value = null;
            selectedImageFile.value = null;
            router.reload({ only: ['questions'], preserveState: true, preserveScroll: true });
        },
    });
}

const showUploadProgress = computed(() => passageForm.processing && (passageForm.audio_file !== null || passageForm.image_file !== null));
const uploadLabel = computed(() => passageForm.audio_file !== null ? 'Mengunggah & mengompres audio...' : 'Mengunggah & mengompres gambar...');

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
            questions: [{
                skill_id: data.skill_id,
                skill_part_id: data.skill_part_id,
                question_text: data.question_text,
                option_a: data.option_a,
                option_b: data.option_b,
                option_c: data.option_c,
                option_d: data.option_d,
                correct_answer: data.correct_answer,
            }],
        }))
        .post(route('content-library.store'), {
            preserveScroll: true,
            onSuccess: () => {
                addingPassageId.value = null;
                quickQuestionForm.reset();
                router.reload({ only: ['questions'], preserveState: true, preserveScroll: true });
            },
        });
}

async function deletePassage(passage) {
    if (!await confirm.confirm(`Hapus materi soal "${passage.title}"? Soal di dalamnya tidak ikut terhapus.`)) return;
    router.delete(route('content-library.passages.destroy', passage.id), {
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

        const pid = q.skill_part_id || 'none';
        if (!bucket.parts.has(pid)) {
            bucket.parts.set(pid, { part: q.skillPart || null, passageGroups: new Map(), standalone: [] });
        }
        const partBucket = bucket.parts.get(pid);

        if (q.passage) {
            if (!partBucket.passageGroups.has(q.passage_id)) {
                partBucket.passageGroups.set(q.passage_id, { passage: q.passage, questions: [] });
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
        .sort((a, b) => (a.skill?.name || '').localeCompare(b.skill?.name || ''));
});

function partGroupTotal(partGroup) {
    return partGroup.groups.reduce((n, g) => n + g.questions.length, 0) + partGroup.standalone.length;
}

function isPartEmpty(partGroup) {
    return partGroup.groups.length === 0 && partGroup.standalone.length === 0;
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
    if (selectedPassageId.value) p.passage_id = selectedPassageId.value;
    if (selectedPartId.value) p.part_id = selectedPartId.value;
    if (searchQuery.value) p.search = searchQuery.value;
    router.get(route('content-library.index'), p, { preserveState: true });
}

function onSearchInput() { clearTimeout(debounceTimer); debounceTimer = setTimeout(applyFilters, 400); }
function resetFilters() {
    searchQuery.value = ''; selectedSkillId.value = ''; selectedBankId.value = '';
    selectedStatus.value = ''; selectedPassageId.value = ''; selectedPartId.value = '';
    applyFilters();
}

async function deleteQuestion(id, text) {
    if (!await confirm.confirm(`Hapus soal: "${text.substring(0, 50)}..."?`)) return;
    router.delete(route('content-library.destroy', id), { preserveScroll: true });
}

async function reviewQuestion(id, status) {
    const note = status === 'rejected' ? await confirm.prompt('Catatan penolakan (opsional):') : null;
    router.patch(route('content-library.review', id), { status, review_note: note }, { preserveScroll: true });
}

function toggleSelectAll() {
    if (selectedIds.value.length === props.questions.data.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.questions.data.map(q => q.id);
    }
}

function isPassageSelected(group) {
    return group.questions.length > 0 &&
        group.questions.every(q => selectedIds.value.includes(q.id));
}

function togglePassageSelection(group) {
    const ids = group.questions.map(q => q.id);
    if (isPassageSelected(group)) {
        selectedIds.value = selectedIds.value.filter(id => !ids.includes(id));
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
        toast.warning('Pilih soal terlebih dahulu.');
        return;
    }
    const note = status === 'rejected' ? await confirm.prompt('Catatan penolakan (opsional):') : null;
    router.patch(route('content-library.bulk-review'), { ids: selectedIds.value, status, review_note: note }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
    });
}
</script>

<template>
    <Head title="Bank Soal" />
    <DashboardLayout title="Bank Soal">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <p class="text-text-body text-text-body text-body-md">{{ questions.total || 0 }} soal ditemukan</p>
                <Link :href="route('content-library.passages.index')" class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                    <IconBooks :size="16" /> Kelola Materi Soal
                </Link>
                <Link v-if="canReview" :href="route('content-library.question-banks.index')" class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                    <IconBooks :size="16" /> Kelola Bank Soal
                </Link>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('content-library.create')"
                      class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                    <IconPlus :size="18" /> Tambah Soal Baru
                </Link>
            </div>
        </div>

        <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 mb-6">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="text-label-md font-medium text-primary block mb-1.5">Cari</label>
                    <div class="relative">
                        <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                        <input type="text" v-model="searchQuery" @input="onSearchInput" placeholder="Cari teks soal..."
                               class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                </div>
                <div class="w-44"><label class="text-label-md font-medium text-primary block mb-1.5">Bank Soal</label>
                    <select v-model="selectedBankId" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua Bank</option>
                        <option v-for="b in questionBanks" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Skill</label>
                    <select v-model="selectedSkillId" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="s in skills" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Part</label>
                    <select v-model="selectedPartId" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="pt in parts" :key="pt.id" :value="pt.id">{{ pt.name }} <template v-if="pt.skill">({{ pt.skill.name }})</template></option>
                    </select>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Materi Soal</label>
                    <select v-model="selectedPassageId" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="p in passages" :key="p.id" :value="p.id">{{ p.title }}</option>
                    </select>
                </div>
                <button @click="resetFilters" class="px-5 py-3 border border-outline-variant rounded-2xl text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Reset</button>
            </div>

            <div class="flex gap-2 mt-4 pt-4 border-t border-outline-variant/30">
                <button v-for="s in statuses" :key="s" @click="selectedStatus = (selectedStatus === s ? '' : s); applyFilters()"
                        class="px-4 py-2 rounded-full text-label-md font-medium border transition-all"
                        :class="selectedStatus === s ? 'bg-primary-container text-white border-primary-container' : 'border-outline-variant text-text-body hover:border-secondary'">
                    {{ statusLabels[s] }}
                </button>
            </div>
        </div>

        <div v-if="selectedPassage" class="bg-pastel-blue/20 border border-pastel-blue/50 rounded-2xl p-4 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h3 class="text-title-md font-semibold text-primary mb-0.5">{{ selectedPassage.title }}</h3>
                    <RichTextViewer v-if="selectedPassage.content_text" :content="selectedPassage.content_text" :clamp="2" class="mt-1" />
                    <p v-else-if="selectedPassage.audio_url" class="text-label-md text-text-body">Audio: {{ selectedPassage.audio_url }}</p>
                    <div class="flex gap-2 mt-2">
                        <span class="text-label-md text-text-muted">{{ questions.total || 0 }} soal dalam materi soal ini</span>
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <Link :href="route('content-library.create')"
                          class="flex items-center gap-1.5 bg-primary-container text-white px-4 py-2 rounded-full text-label-md font-medium hover:bg-primary transition-all">
                        <IconPlus :size="16" /> Tambah Soal
                    </Link>
                    <button @click="selectedPassageId = ''; applyFilters()"
                            class="flex items-center gap-1.5 px-4 py-2 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                        <IconX :size="16" /> Hapus Filter
                    </button>
                </div>
            </div>
        </div>

        <div v-if="questions.data?.length === 0" class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <IconBook class="mx-auto text-text-muted mb-3" :size="48" stroke="1.5" />
            <p class="text-text-body text-text-body text-body-md">Tidak ada soal ditemukan.</p>
        </div>

        <div v-else class="space-y-8">
            <template v-for="skillGroup in skillGroups" :key="skillGroup.skill?.id || 'no-skill'">
                <!-- HEADER SKILL -->
                <div class="flex items-center gap-3">
                    <span class="inline-block bg-primary-container text-white px-3.5 py-1.5 rounded-full text-label-md font-semibold">{{ skillGroup.skill?.name || 'Tanpa Skill' }}</span>
                    <span class="text-label-md text-text-muted">{{ skillGroupTotal(skillGroup) }} soal</span>
                </div>

                <div class="space-y-6">
                    <template v-for="partGroup in skillGroup.parts" :key="partGroup.part?.id || 'no-part'">
                        <!-- HEADER PART -->
                        <div class="flex items-center gap-2">
                            <span class="inline-block bg-pastel-purple/30 text-primary px-3.5 py-1.5 rounded-full text-label-md font-semibold">{{ partGroup.part?.name || 'Tanpa Part' }}</span>
                            <span class="text-label-md text-text-muted">{{ partGroupTotal(partGroup) }} soal</span>
                        </div>

                        <div class="space-y-4">
                            <!-- GROUP PASSAGE -->
                            <div v-for="group in partGroup.groups" :key="group.passage.id"
                 class="bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden">
                <div class="px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/30">
                    <!-- TAMPILAN HEADER NORMAL -->
                    <template v-if="editingPassageId !== group.passage.id">
                        <div class="flex items-center gap-2 flex-wrap">
                            <input v-if="canReview && group.questions.length" type="checkbox" :checked="isPassageSelected(group)" @change="togglePassageSelection(group)"
                                   class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary shrink-0"
                                   title="Pilih semua soal di materi ini" />
                            <IconFileDescription :size="20" class="text-secondary shrink-0" />
                            <h3 class="text-title-md font-semibold text-primary">{{ group.passage.title }}</h3>
                            <span class="inline-block bg-pastel-purple/50 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ passageTypeLabel(group.passage.type) }}</span>
                            <span class="text-label-md text-text-muted">{{ group.questions.length }} soal</span>
                            <div class="ml-auto flex items-center gap-1">
                                <Link v-if="group.questions.length" :href="route('content-library.preview', group.questions[0].id)"
                                      class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Preview soal materi ini">
                                    <IconEye :size="16" />
                                </Link>
                                <button @click="addingPassageId === group.passage.id ? closeQuickAdd() : openQuickAdd(group.passage)"
                                        class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-label-md font-medium text-secondary hover:bg-surface-container-highest transition-colors"
                                        title="Tambah soal ke materi soal ini">
                                    <IconPlus :size="16" /> Soal
                                </button>
                                <button @click="startEditPassage(group.passage)" class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Edit passage"><IconEdit :size="16" /></button>
                                <button @click="deletePassage(group.passage)"
                                        :disabled="group.questions.length > 0"
                                        :title="group.questions.length > 0 ? `Tidak bisa dihapus — materi soal dipakai ${group.questions.length} soal` : 'Hapus materi soal'"
                                        class="p-1.5 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted">
                                    <IconTrash :size="16" />
                                </button>
                            </div>
                        </div>

                        <div v-if="group.passage.type === 'audio' && group.passage.audio_url" class="mt-2">
                            <audio controls :src="'/storage/' + group.passage.audio_url" class="w-full max-w-md h-9"></audio>
                        </div>
                        <div v-else-if="group.passage.type === 'image' && group.passage.image_url" class="mt-2">
                            <img :src="'/storage/' + group.passage.image_url" class="max-h-44 rounded-xl border border-outline-variant/30 object-contain" />
                        </div>
                        <template v-else-if="group.passage.content_text">
                            <RichTextViewer :content="group.passage.content_text"
                                            :clamp="isPassageExpanded(group.passage.id) ? 0 : 3"
                                            class="mt-2" />
                            <button v-if="group.passage.content_text.length > 180" @click="togglePassage(group.passage.id)"
                                    class="mt-1 text-secondary text-label-md font-medium hover:underline">
                                {{ isPassageExpanded(group.passage.id) ? 'Sembunyikan ▲' : 'Lihat Selengkapnya ▼' }}
                            </button>
                        </template>
                    </template>

                    <!-- FORM EDIT PASSAGE INLINE -->
                    <template v-else>
                        <p class="text-label-md font-semibold text-primary mb-3">Edit Materi Soal</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Judul Materi Soal</label>
                                <input type="text" v-model="passageForm.title" placeholder="Judul passage"
                                       class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                <p v-if="passageForm.errors.title" class="text-error-red text-xs mt-1">{{ passageForm.errors.title }}</p>
                            </div>
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Tipe Materi Soal</label>
                                <select v-model="passageForm.type" @change="passageEditType = passageForm.type"
                                        class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                                    <option value="text">Teks</option>
                                    <option value="audio">Audio</option>
                                    <option value="image">Gambar</option>
                                    <option value="prompt">Prompt</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="passageEditType === 'text' || passageEditType === 'prompt'" class="mt-3">
                            <label class="text-label-md font-medium text-primary block mb-1">Konten Teks</label>
                            <RichTextEditor v-model="passageForm.content_text" placeholder="Isi teks passage..." />
                        </div>

                        <div v-else-if="passageEditType === 'audio'" class="mt-3">
                            <label class="text-label-md font-medium text-primary block mb-1">File Audio <span class="text-text-muted">(mp3/wav/m4a, max 50MB)</span></label>
                            <audio v-if="group.passage.audio_url" controls :src="'/storage/' + group.passage.audio_url" class="w-full max-w-md h-9 mb-2"></audio>
                            <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors">
                                <IconUpload :size="18" class="text-text-muted" />
                                <span class="text-label-md text-text-body">{{ selectedAudioFile ? selectedAudioFile.name : (group.passage.audio_url ? 'Ganti file audio' : 'Upload audio') }}</span>
                                <input type="file" accept=".mp3,.wav,.ogg,.m4a" class="hidden" @change="onEditAudioSelect" />
                            </label>
                            <p v-if="passageForm.errors.audio_file" class="text-error-red text-xs mt-1">{{ passageForm.errors.audio_file }}</p>
                        </div>

                        <div v-else-if="passageEditType === 'image'" class="mt-3">
                            <label class="text-label-md font-medium text-primary block mb-1">File Gambar <span class="text-text-muted">(jpg/png/webp, max 20MB, otomatis dikompres)</span></label>
                            <img v-if="group.passage.image_url" :src="'/storage/' + group.passage.image_url" class="max-h-32 rounded-lg border border-outline-variant/30 object-contain mb-2" />
                            <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors">
                                <IconUpload :size="18" class="text-text-muted" />
                                <span class="text-label-md text-text-body">{{ selectedImageFile ? selectedImageFile.name : (group.passage.image_url ? 'Ganti file gambar' : 'Upload gambar') }}</span>
                                <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onEditImageSelect" />
                            </label>
                            <p v-if="passageForm.errors.image_file" class="text-error-red text-xs mt-1">{{ passageForm.errors.image_file }}</p>
                        </div>

                        <div class="flex items-center gap-2 mt-4">
                            <button @click="savePassage(group.passage)" :disabled="passageForm.processing"
                                    class="flex items-center gap-1.5 bg-primary-container text-white px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                                <IconCheck :size="16" /> {{ passageForm.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                            <button @click="cancelEditPassage"
                                    class="flex items-center gap-1.5 px-5 py-2.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                                Batal
                            </button>
                        </div>
                    </template>
                </div>

                <!-- QUICK ADD SOAL KE PASSAGE -->
                <div v-if="addingPassageId === group.passage.id"
                     class="px-5 py-4 bg-pastel-blue/10 border-b border-outline-variant/30">
                    <p class="text-label-md font-semibold text-primary mb-3">Tambah Soal ke "{{ group.passage.title }}"</p>
                    <form @submit.prevent="saveQuickQuestion" class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Bank Soal <span class="text-error-red">*</span></label>
                                <select v-model="quickQuestionForm.question_bank_id" required
                                        class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                                    <option value="" disabled>Pilih Bank</option>
                                    <option v-for="b in questionBanks" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <p v-if="quickQuestionForm.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ quickQuestionForm.errors.question_bank_id }}</p>
                            </div>
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Skill <span class="text-error-red">*</span></label>
                                <select v-model="quickQuestionForm.skill_id" @change="onQuickSkillChange" required
                                        class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                                    <option value="" disabled>Pilih Skill</option>
                                    <option v-for="s in availableQuickSkills" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Part <span class="text-error-red">*</span></label>
                                <select v-model="quickQuestionForm.skill_part_id" required :disabled="!quickQuestionForm.skill_id"
                                        class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary disabled:opacity-50">
                                    <option value="" disabled>Pilih part</option>
                                    <option v-for="p in quickParts()" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Kunci Jawaban <span class="text-error-red">*</span></label>
                                <select v-model="quickQuestionForm.correct_answer" required
                                        class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                                    <option value="" disabled>Pilih kunci</option>
                                    <option v-for="key in optionKeys" :key="key" :value="key">{{ key }}</option>
                                </select>
                            </div>
                        </div>
                        <template v-if="quickIsListening">
                            <p class="flex items-center gap-1.5 text-label-md text-text-muted bg-pastel-purple/10 border border-pastel-purple/40 rounded-xl px-4 py-3">
                                <IconHeadphones :size="16" class="text-secondary shrink-0" />
                                Soal listening memakai audio passage ini — tanpa teks soal dan pilihan jawaban.
                            </p>
                        </template>
                        <template v-else>
                            <div>
                                <label class="text-label-md font-medium text-primary block mb-1">Teks Soal <span class="text-error-red">*</span></label>
                                <textarea v-model="quickQuestionForm.question_text" rows="2" required
                                          class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary"></textarea>
                                <p v-if="quickQuestionForm.errors.question_text" class="text-error-red text-xs mt-1">{{ quickQuestionForm.errors.question_text }}</p>
                            </div>
                            <div>
                                <p class="text-label-md font-medium text-primary mb-2">Pilihan Jawaban <span class="text-error-red">*</span></p>
                                <div class="space-y-2">
                                    <div v-for="key in optionKeys" :key="key" class="flex items-center gap-2">
                                        <span class="w-7 h-7 shrink-0 flex items-center justify-center rounded-full bg-surface-white border border-outline-variant font-semibold text-primary text-sm">{{ key }}</span>
                                        <input type="text" v-model="quickQuestionForm['option_' + key.toLowerCase()]" required :placeholder="'Teks pilihan ' + key"
                                               class="flex-1 px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="flex items-center gap-2">
                            <button type="submit" :disabled="quickQuestionForm.processing"
                                    class="flex items-center gap-1.5 bg-primary-container text-white px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                                <IconCheck :size="16" /> {{ quickQuestionForm.processing ? 'Menyimpan...' : 'Simpan Soal' }}
                            </button>
                            <button type="button" @click="closeQuickAdd"
                                    class="flex items-center gap-1.5 px-5 py-2.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                <th v-if="canReview" class="px-5 py-4"></th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Soal</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Bank Soal</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Skill</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Pembuat</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="q in group.questions" :key="q.id"
                                class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                                <td v-if="canReview" class="px-5 py-4">
                                    <input type="checkbox" :checked="selectedIds.includes(q.id)" @change="toggleSelect(q.id)"
                                           class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                                </td>
                                <td class="px-5 py-4 min-w-[260px] max-w-md">
                                    <span v-if="q.audio_url || q.passage?.audio_url" class="inline-flex items-center gap-1 bg-pastel-purple/30 text-primary px-2 py-0.5 rounded-full text-label-md font-medium mb-1"><IconHeadphones :size="12" /> Audio</span>
                                    <p class="text-text-body text-body-md text-primary font-medium line-clamp-2">{{ q.question_text }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span v-if="q.question_bank_id" class="inline-block bg-pastel-peach/50 text-amber-800 dark:text-amber-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ bankName(q.question_bank_id) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span v-if="q.skill_id" class="inline-block bg-pastel-blue/50 text-blue-700 dark:text-blue-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ skillName(q.skill_id) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="statusColors[q.status] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                                          class="inline-block px-2.5 py-0.5 rounded-full text-label-md">{{ statusLabels[q.status] || q.status }}</span>
                                </td>
                                <td class="px-5 py-4 text-body-md text-text-body whitespace-nowrap">{{ q.creator?.name || '-' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1">
                                        <template v-if="canReview && q.status === 'draft'">
                                            <button @click="reviewQuestion(q.id, 'approved')" class="p-2 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-400 transition-colors" title="Setujui"><IconCheck :size="18" /></button>
                                            <button @click="reviewQuestion(q.id, 'rejected')" class="p-2 text-error-red hover:text-red-700 dark:hover:text-red-400 transition-colors" title="Tolak"><IconX :size="18" /></button>
                                        </template>
                                        <Link :href="route('content-library.preview', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Preview"><IconEye :size="18" /></Link>
                                        <Link :href="route('content-library.edit', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></Link>
                                        <button @click="deleteQuestion(q.id, q.question_text)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

                    <!-- SOAL STANDALONE (per part) -->
                    <div v-if="partGroup.standalone.length">
                        <div class="flex items-center gap-2 px-1 mb-3">
                            <IconBook :size="16" class="text-text-muted" />
                            <p class="text-label-md font-semibold text-text-muted uppercase tracking-wider">Soal Standalone</p>
                        </div>
                        <div class="bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                            <th v-if="canReview" class="px-5 py-4"></th>
                                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Soal</th>
                                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Bank Soal</th>
                                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Skill</th>
                                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Pembuat</th>
                                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="q in partGroup.standalone" :key="q.id"
                                            class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                                            <td v-if="canReview" class="px-5 py-4">
                                                <input type="checkbox" :checked="selectedIds.includes(q.id)" @change="toggleSelect(q.id)"
                                                       class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                                            </td>
                                            <td class="px-5 py-4 min-w-[260px] max-w-md">
                                                <p class="text-text-body text-body-md text-primary font-medium line-clamp-2">{{ q.question_text }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span v-if="q.question_bank_id" class="inline-block bg-pastel-peach/50 text-amber-800 dark:text-amber-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ bankName(q.question_bank_id) }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span v-if="q.skill_id" class="inline-block bg-pastel-blue/50 text-blue-700 dark:text-blue-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ skillName(q.skill_id) }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span :class="statusColors[q.status] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                                                      class="inline-block px-2.5 py-0.5 rounded-full text-label-md">{{ statusLabels[q.status] || q.status }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-body-md text-text-body whitespace-nowrap">{{ q.creator?.name || '-' }}</td>
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-1">
                                                    <template v-if="canReview && q.status === 'draft'">
                                                        <button @click="reviewQuestion(q.id, 'approved')" class="p-2 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-400 transition-colors" title="Setujui"><IconCheck :size="18" /></button>
                                                        <button @click="reviewQuestion(q.id, 'rejected')" class="p-2 text-error-red hover:text-red-700 dark:hover:text-red-400 transition-colors" title="Tolak"><IconX :size="18" /></button>
                                                    </template>
                                        <Link :href="route('content-library.preview', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Preview"><IconEye :size="18" /></Link>
                                        <Link :href="route('content-library.edit', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></Link>
                                                    <button @click="deleteQuestion(q.id, q.question_text)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

        <div v-if="questions.total > questions.per_page" class="flex justify-center mt-6 gap-2">
            <Link v-for="link in questions.links" :key="link.label"
                  :href="link.url || '#'"
                  class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                  :class="link.active ? 'bg-primary-container text-white' : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                  v-html="link.label" />
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" />

        <Transition name="fade-slide">
            <div v-if="canReview && selectedIds.length > 0"
                 class="fixed left-1/2 -translate-x-1/2 bottom-[76px] md:bottom-6 z-40 bg-surface-white rounded-full shadow-lg border border-outline-variant/30 px-4 py-3 flex items-center gap-3">
                <span class="text-label-md font-semibold text-primary whitespace-nowrap">{{ selectedIds.length }} soal dipilih</span>
                <span class="w-px h-5 bg-outline-variant/40"></span>
                <button @click="bulkReview('approved')" class="flex items-center gap-1 px-4 py-2 bg-green-600 text-white rounded-full text-label-md font-medium hover:bg-green-700 transition-all"><IconCheck :size="16" />Setujui</button>
                <button @click="bulkReview('rejected')" class="flex items-center gap-1 px-4 py-2 bg-error-red text-white rounded-full text-label-md font-medium hover:bg-red-700 transition-all"><IconX :size="16" />Tolak</button>
                <button @click="selectedIds = []" class="px-3 py-2 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Batal</button>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-slide-enter-from, .fade-slide-leave-to {
    opacity: 0;
    transform: translate(-50%, 10px);
}
</style>
