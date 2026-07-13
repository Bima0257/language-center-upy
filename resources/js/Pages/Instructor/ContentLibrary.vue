<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconSearch, IconTrash, IconEdit, IconBook, IconFileDescription, IconPlus, IconTags } from '@tabler/icons-vue';
import { ref } from 'vue';

const props = defineProps({
    questions: { type: Object, default: () => ({ data: [] }) },
    exams: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search || '');
const selectedExam = ref(props.filters.exam_id || '');
const selectedSection = ref(props.filters.section_type || '');
const selectedType = ref(props.filters.question_type || '');
const selectedDifficulty = ref(props.filters.difficulty || '');
const selectedStatus = ref(props.filters.status || '');
const selectedTag = ref(props.filters.tag_id || '');

const typeLabels = {
    multiple_choice: 'Pilihan Ganda', multi_select: 'Pilih >1 Jawaban',
    order: 'Urutkan', matching: 'Menjodohkan',
    fill_blank: 'Isian Singkat', essay: 'Essay',
    speaking: 'Speaking', true_false: 'True / False / Not Given',
    dictation: 'Dikte', error_id: 'Identifikasi Error',
};

const sectionTypeLabels = { reading: 'Membaca', listening: 'Mendengar', speaking: 'Berbicara', writing: 'Menulis', grammar: 'Tata Bahasa', vocabulary: 'Kosakata' };

const diffColors = { easy: 'bg-green-100 text-green-700', medium: 'bg-amber-100 text-amber-700', hard: 'bg-error-red/10 text-error-red' };
const diffLabels = { easy: 'Mudah', medium: 'Sedang', hard: 'Sulit' };
const statusLabels = { draft: 'Draf', active: 'Aktif', archived: 'Arsip' };
const statusColors = { draft: 'bg-gray-100 text-gray-600', active: 'bg-green-100 text-green-700', archived: 'bg-amber-100 text-amber-700' };

let debounceTimer = null;

function applyFilters() {
    const p = {};
    if (selectedExam.value) p.exam_id = selectedExam.value;
    if (selectedSection.value) p.section_type = selectedSection.value;
    if (selectedType.value) p.question_type = selectedType.value;
    if (selectedDifficulty.value) p.difficulty = selectedDifficulty.value;
    if (selectedStatus.value) p.status = selectedStatus.value;
    if (selectedTag.value) p.tag_id = selectedTag.value;
    if (searchQuery.value) p.search = searchQuery.value;
    router.get(route('content-library.index'), p, { preserveState: true });
}

function onSearchInput() { clearTimeout(debounceTimer); debounceTimer = setTimeout(applyFilters, 400); }
function resetFilters() {
    searchQuery.value = ''; selectedExam.value = ''; selectedSection.value = '';
    selectedType.value = ''; selectedDifficulty.value = ''; selectedStatus.value = ''; selectedTag.value = '';
    applyFilters();
}

function deleteQuestion(id, text) {
    if (!confirm(`Hapus soal: "${text.substring(0, 50)}..."?`)) return;
    router.delete(route('content-library.destroy', id), { preserveScroll: true });
}
function parseOpts(o) { try { return typeof o === 'string' ? JSON.parse(o) : o; } catch { return []; } }
</script>

<template>
    <Head title="Bank Soal" />
    <DashboardLayout title="Bank Soal">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <p class="text-text-body text-body-md">{{ questions.total || 0 }} soal ditemukan</p>
                <Link :href="route('content-library.tags.index')" class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                    <IconTags :size="16" /> Kelola Tag
                </Link>
            </div>
            <Link :href="route('content-library.create')"
                  class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                <IconPlus :size="18" /> Tambah Soal
            </Link>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 mb-6">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="text-label-md font-medium text-primary block mb-1.5">Cari</label>
                    <div class="relative">
                        <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                        <input type="text" v-model="searchQuery" @input="onSearchInput" placeholder="Cari teks soal..."
                               class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Exam</label>
                    <select v-model="selectedExam" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="e in exams" :key="e.id" :value="e.id">{{ e.title }}</option>
                    </select>
                </div>
                <div class="w-32"><label class="text-label-md font-medium text-primary block mb-1.5">Section</label>
                    <select v-model="selectedSection" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="(l,k) in sectionTypeLabels" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="w-36"><label class="text-label-md font-medium text-primary block mb-1.5">Tipe Soal</label>
                    <select v-model="selectedType" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="(l,k) in typeLabels" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="w-28"><label class="text-label-md font-medium text-primary block mb-1.5">Difficulty</label>
                    <select v-model="selectedDifficulty" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option value="easy">Mudah</option>
                        <option value="medium">Sedang</option>
                        <option value="hard">Sulit</option>
                    </select>
                </div>
                <div class="w-28"><label class="text-label-md font-medium text-primary block mb-1.5">Status</label>
                    <select v-model="selectedStatus" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="(l,k) in statusLabels" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="w-32"><label class="text-label-md font-medium text-primary block mb-1.5">Tag</label>
                    <select v-model="selectedTag" @change="applyFilters"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="">Semua</option>
                        <option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
                <button @click="resetFilters" class="px-5 py-3 border border-outline-variant rounded-2xl text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Reset</button>
            </div>
        </div>

        <div v-if="questions.data?.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <IconBook class="mx-auto text-text-muted mb-3" :size="48" stroke="1.5" />
            <p class="text-text-body text-body-md">Tidak ada soal ditemukan.</p>
        </div>

        <div v-else class="space-y-3">
            <div v-for="q in questions.data" :key="q.id"
                 class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 hover:border-secondary/50 transition-all">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="inline-block bg-pastel-purple/50 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ typeLabels[q.type] || q.type }}</span>
                            <span :class="diffColors[q.difficulty] || 'bg-gray-100 text-gray-600'" class="px-2.5 py-0.5 rounded-full text-label-md">{{ diffLabels[q.difficulty] || q.difficulty }}</span>
                            <span :class="statusColors[q.status] || 'bg-gray-100 text-gray-600'" class="px-2.5 py-0.5 rounded-full text-label-md">{{ statusLabels[q.status] || q.status }}</span>
                        </div>
                        <p class="text-body-md text-primary font-medium mb-1">{{ q.question_text }}</p>
                        <div v-if="q.options" class="flex flex-wrap gap-2 mt-2">
                            <span v-for="opt in parseOpts(q.options)" :key="opt.key"
                                  class="inline-block bg-surface-container-low px-2.5 py-1 rounded-lg text-label-md text-text-body">{{ opt.key }}. {{ opt.text?.substring(0, 40) }}</span>
                        </div>
                        <div v-if="q.tags?.length" class="flex flex-wrap gap-1.5 mt-2">
                            <span v-for="tag in q.tags" :key="tag.id"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-label-md"
                                  :class="tag.type === 'skill' ? 'bg-pastel-purple/30' : tag.type === 'topic' ? 'bg-pastel-blue/30' : 'bg-pastel-peach/30'">
                                <IconTags :size="12" />{{ tag.name }}
                            </span>
                        </div>
                        <p class="text-label-md text-text-muted mt-2">
                            {{ q.points }} pt | Urutan {{ q.order }}
                            <span v-if="q.creator"> | oleh {{ q.creator.name }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <Link :href="route('content-library.edit', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></Link>
                        <Link :href="route('admin.exams.show', q.question_group?.section?.exam_id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ke Exam"><IconFileDescription :size="18" /></Link>
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
