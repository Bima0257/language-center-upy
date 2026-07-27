<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconSearch, IconTrash, IconEdit, IconBook, IconTags, IconPlus, IconCheck, IconX, IconFileDescription, IconBooks } from '@tabler/icons-vue';
import { ref, computed } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    questions: { type: Object, default: () => ({ data: [] }) },
    tags: { type: Array, default: () => [] },
    passages: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ['draft', 'submitted', 'approved', 'rejected', 'archived'] },
    selectedPassage: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const canReview = computed(() => {
    const roles = page.props.auth?.roles || [];
    return roles.some(r => r === 'admin' || r === 'superadmin');
});

const searchQuery = ref(props.filters.search || '');
const selectedSkill = ref(props.filters.skill || '');
const selectedType = ref(props.filters.question_type || '');
const selectedDifficulty = ref(props.filters.difficulty || '');
const selectedStatus = ref(props.filters.status || '');
const selectedTag = ref(props.filters.tag_id || '');
const selectedPassageId = ref(props.filters.passage_id || '');
const selectedIds = ref([]);

const typeLabels = {
    multiple_choice: 'Pilihan Ganda', multi_select: 'Pilih >1 Jawaban',
    order: 'Urutkan', matching: 'Menjodohkan',
    fill_blank: 'Isian Singkat', essay: 'Essay',
    speaking: 'Speaking', true_false: 'True / False / Not Given',
    dictation: 'Dikte', error_id: 'Identifikasi Error',
};

const skillLabels = { reading: 'Reading', listening: 'Listening', speaking: 'Speaking', writing: 'Writing', grammar: 'Grammar', vocabulary: 'Vocabulary' };
const diffColors = { easy: 'bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300', medium: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300', hard: 'bg-error-red/10 text-error-red' };
const diffLabels = { easy: 'Mudah', medium: 'Sedang', hard: 'Sulit' };
const statusLabels = { draft: 'Draf', submitted: 'Diajukan', approved: 'Disetujui', rejected: 'Ditolak', archived: 'Arsip' };
const statusColors = { draft: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400', submitted: 'bg-blue-100 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300', approved: 'bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300', rejected: 'bg-error-red/10 text-error-red', archived: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300' };

let debounceTimer = null;

function applyFilters() {
    const p = {};
    if (selectedSkill.value) p.skill = selectedSkill.value;
    if (selectedType.value) p.question_type = selectedType.value;
    if (selectedDifficulty.value) p.difficulty = selectedDifficulty.value;
    if (selectedStatus.value) p.status = selectedStatus.value;
    if (selectedTag.value) p.tag_id = selectedTag.value;
    if (selectedPassageId.value) p.passage_id = selectedPassageId.value;
    if (searchQuery.value) p.search = searchQuery.value;
    router.get(route('content-library.index'), p, { preserveState: true });
}

function onSearchInput() { clearTimeout(debounceTimer); debounceTimer = setTimeout(applyFilters, 400); }
function resetFilters() {
    searchQuery.value = ''; selectedSkill.value = ''; selectedType.value = '';
    selectedDifficulty.value = ''; selectedStatus.value = ''; selectedTag.value = '';
    selectedPassageId.value = '';
    applyFilters();
}

async function deleteQuestion(id, text) {
    if (!await confirm.confirm(`Hapus soal: "${text.substring(0, 50)}..."?`)) return;
    router.delete(route('content-library.destroy', id), { preserveScroll: true });
}

async function reviewQuestion(id, status) {
    const note = status === 'rejected' ? await confirm.prompt('Catatan penolakan (opsional):') : null;
    router.put(route('content-library.review', id), { status, review_note: note }, { preserveScroll: true });
}

function toggleSelectAll() {
    if (selectedIds.value.length === props.questions.data.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.questions.data.map(q => q.id);
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
    router.put(route('content-library.bulk-review'), { ids: selectedIds.value, status, review_note: note }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
    });
}

function parseOpts(o) { try { return typeof o === 'string' ? JSON.parse(o) : o; } catch { return []; } }
</script>

<template>
    <Head title="Bank Soal" />
    <DashboardLayout title="Bank Soal">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <p class="text-text-body text-text-body text-body-md">{{ questions.total || 0 }} soal ditemukan</p>
                <Link :href="route('content-library.passages.index')" class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                    <IconBooks :size="16" /> Kelola Passage
                </Link>
                <Link :href="route('content-library.tags.index')" class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                    <IconTags :size="16" /> Kelola Tag
                </Link>
            </div>
            <div class="flex items-center gap-3">
                <div v-if="canReview && selectedIds.length > 0" class="flex items-center gap-2">
                    <span class="text-label-md text-text-body">{{ selectedIds.length }} dipilih</span>
                    <button @click="bulkReview('approved')" class="flex items-center gap-1 px-4 py-2 bg-green-600 text-white rounded-full text-label-md font-medium hover:bg-green-700 transition-all"><IconCheck :size="16" />Setujui</button>
                    <button @click="bulkReview('rejected')" class="flex items-center gap-1 px-4 py-2 bg-error-red text-white rounded-full text-label-md font-medium hover:bg-red-700 transition-all"><IconX :size="16" />Tolak</button>
                </div>
                <Link :href="route('content-library.create')"
                      class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                    <IconPlus :size="18" /> Tambah Soal
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
                <div class="w-32"><label class="text-label-md font-medium text-primary block mb-1.5">Skill</label>
                    <select v-model="selectedSkill" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="(l,k) in skillLabels" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Tipe Soal</label>
                    <select v-model="selectedType" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="(l,k) in typeLabels" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="w-28"><label class="text-label-md font-medium text-primary block mb-1.5">Difficulty</label>
                    <select v-model="selectedDifficulty" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option value="easy">Mudah</option>
                        <option value="medium">Sedang</option>
                        <option value="hard">Sulit</option>
                    </select>
                </div>
                <div class="w-32"><label class="text-label-md font-medium text-primary block mb-1.5">Tag</label>
                    <select v-model="selectedTag" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Passage</label>
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
                    <p v-if="selectedPassage.content_text" class="text-label-md text-text-body line-clamp-2">{{ selectedPassage.content_text?.substring(0, 200) }}</p>
                    <p v-else-if="selectedPassage.audio_url" class="text-label-md text-text-body">Audio: {{ selectedPassage.audio_url }}</p>
                    <div class="flex gap-2 mt-2">
                        <span class="text-label-md text-text-muted">{{ questions.total || 0 }} soal dalam passage ini</span>
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <Link :href="route('content-library.create', { passage_id: selectedPassage.id })"
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

        <div v-else class="space-y-3">
            <div v-for="q in questions.data" :key="q.id"
                 class="bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 hover:border-secondary/50 transition-all"
                 :class="{ 'border-secondary/30 bg-secondary/5': selectedIds.includes(q.id) }">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <input v-if="canReview" type="checkbox" :checked="selectedIds.includes(q.id)" @change="toggleSelect(q.id)"
                               class="mt-1.5 w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span v-if="q.skill" class="inline-block bg-pastel-blue/50 text-blue-700 dark:text-blue-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ skillLabels[q.skill] || q.skill }}</span>
                                <span class="inline-block bg-pastel-purple/50 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ typeLabels[q.type] || q.type }}</span>
                                <span :class="diffColors[q.difficulty] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-2.5 py-0.5 rounded-full text-label-md">{{ diffLabels[q.difficulty] || q.difficulty }}</span>
                                <span :class="statusColors[q.status] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-2.5 py-0.5 rounded-full text-label-md">{{ statusLabels[q.status] || q.status }}</span>
                            </div>
                            <p class="text-text-body text-body-md text-primary font-medium mb-1">{{ q.question_text }}</p>
                            <div v-if="q.options" class="flex flex-wrap gap-2 mt-2">
                                <span v-for="opt in parseOpts(q.options)" :key="opt.key"
                                      class="inline-block bg-surface-container-low px-2.5 py-1 rounded-lg text-label-md text-text-body">{{ opt.key }}. {{ opt.text?.substring(0, 40) }}</span>
                            </div>
                            <div v-if="q.tags?.length" class="flex flex-wrap gap-1.5 mt-2">
                                <span v-for="tag in q.tags" :key="tag.id"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-primary text-label-md"
                                      :class="tag.type === 'skill' ? 'bg-pastel-purple/30' : tag.type === 'topic' ? 'bg-pastel-blue/30' : 'bg-pastel-peach/30'">
                                    <IconTags :size="12" />{{ tag.name }}
                                </span>
                            </div>
                            <p class="text-label-md text-text-muted mt-2">
                                {{ q.points }} pt
                                <span v-if="q.passage"> | {{ q.passage.title }}</span>
                                <span v-if="q.creator"> | oleh {{ q.creator.name }}</span>
                                <span v-if="q.reviewed_at"> | direview {{ new Date(q.reviewed_at).toLocaleDateString('id') }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <template v-if="canReview && q.status === 'submitted'">
                            <button @click="reviewQuestion(q.id, 'approved')" class="p-2 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-400 transition-colors" title="Setujui"><IconCheck :size="18" /></button>
                            <button @click="reviewQuestion(q.id, 'rejected')" class="p-2 text-error-red hover:text-red-700 dark:hover:text-red-400 transition-colors" title="Tolak"><IconX :size="18" /></button>
                        </template>
                        <Link :href="route('content-library.edit', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></Link>
                        <button @click="deleteQuestion(q.id, q.question_text)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="questions.total > questions.per_page" class="flex justify-center mt-6 gap-2">
            <Link v-for="link in questions.links" :key="link.label"
                  :href="link.url || '#'"
                  class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                  :class="link.active ? 'bg-primary-container text-white' : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                  v-html="link.label" />
        </div>
    </DashboardLayout>
</template>
