<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import QuestionEditor from '@/Components/Admin/QuestionEditor.vue';
import BulkQuestionImport from '@/Components/Admin/BulkQuestionImport.vue';
import { IconPlus, IconTrash, IconEdit, IconChevronDown, IconChevronRight, IconFileDescription, IconUpload } from '@tabler/icons-vue';
import { ref } from 'vue';

const props = defineProps({
    exam: { type: Object, required: true },
});

const expandedSections = ref({});
const editingGroup = ref(null);
const importGroup = ref(null);

function toggleSection(id) {
    expandedSections.value[id] = !expandedSections.value[id];
}

const sectionForm = useForm({
    title: '',
    skill: 'reading',
    order: 1,
    duration_minutes: null,
    instructions: '',
});

function addSection() {
    sectionForm.post(route('admin.exams.sections.store', props.exam.id), {
        preserveScroll: true,
        onSuccess: () => sectionForm.reset(),
    });
}

const groupForm = useForm({
    title: '',
    passage_text: '',
    order: 1,
});

function addGroup(sectionId) {
    const section = props.exam.sections.find(s => s.id === sectionId);
    const nextOrder = (section?.question_groups?.length || 0) + 1;
    groupForm.order = nextOrder;
    groupForm.post(route('admin.exams.groups.store', sectionId), {
        preserveScroll: true,
        onSuccess: () => { groupForm.reset(); groupForm.title = ''; groupForm.passage_text = ''; },
    });
}

function deleteQuestion(questionId) {
    if (confirm('Hapus soal ini?')) {
        const form = useForm({});
        form.delete(route('admin.exams.questions.destroy', questionId), { preserveScroll: true });
    }
}

function deleteGroup(groupId) {
    if (confirm('Hapus grup soal ini? Semua soal di dalamnya akan ikut terhapus.')) {
        const form = useForm({});
        form.delete(route('admin.exams.groups.destroy', groupId), { preserveScroll: true });
    }
}

function deleteSection(sectionId) {
    if (confirm('Hapus section ini? Semua grup dan soal di dalamnya akan terhapus.')) {
        const form = useForm({});
        form.delete(route('admin.exams.sections.destroy', [props.exam.id, sectionId]), { preserveScroll: true });
    }
}

const typeLabels = { reading: '📖', listening: '🎧', speaking: '🎤', writing: '✍️', grammar: '📝', vocabulary: '📚' };
</script>

<template>
    <Head :title="exam.title" />
    <DashboardLayout :title="exam.title">
        <div class="space-y-6">
            <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
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
                <p v-if="exam.description" class="text-text-body text-body-md">{{ exam.description }}</p>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
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
                                <span>{{ typeLabels[section.skill] || '' }}</span>
                                <div>
                                    <p class="font-semibold text-primary">{{ section.title }}</p>
                                    <p class="text-text-muted text-label-md">{{ section.question_groups?.length || 0 }} grup — {{ section.total_questions }} soal</p>
                                </div>
                            </div>
                            <button @click.stop="deleteSection(section.id)"
                                    class="p-2 text-text-muted hover:text-error-red transition-colors">
                                <IconTrash :size="18" />
                            </button>
                        </div>

                        <div v-if="expandedSections[section.id]" class="p-4 space-y-4 border-t border-outline-variant/30">
                            <div v-if="section.question_groups?.length" class="space-y-3">
                                <div v-for="group in section.question_groups" :key="group.id"
                                     class="bg-surface-white rounded-2xl p-4 border border-outline-variant/30">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <IconFileDescription class="text-text-muted" :size="18" stroke="1.5" />
                                            <div>
                                                <p class="font-medium text-primary">{{ group.title || 'Untitled Group' }}</p>
                                                <p class="text-text-muted text-label-md">{{ group.questions?.length || 0 }} soal</p>
                                            </div>
                                        </div>
                                        <button @click="deleteGroup(group.id)"
                                                class="p-1.5 text-text-muted hover:text-error-red transition-colors">
                                            <IconTrash :size="16" />
                                        </button>
                                    </div>

                                    <div v-if="group.questions?.length" class="space-y-2 mb-3">
                                        <div v-for="q in group.questions" :key="q.id"
                                             class="flex items-start justify-between p-3 bg-surface-container-low rounded-xl gap-3">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-body-md text-primary truncate">{{ q.question_text }}</p>
                                                <p class="text-label-md text-text-muted">{{ q.type }} — {{ q.points }} pt</p>
                                            </div>
                                            <button @click="deleteQuestion(q.id)"
                                                    class="p-1.5 shrink-0 text-text-muted hover:text-error-red transition-colors">
                                                <IconTrash :size="14" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-3">
                                        <p class="text-text-muted text-label-md">Belum ada soal di grup ini.</p>
                                    </div>

                                    <div class="flex gap-2 mt-3">
                                        <button @click="editingGroup = editingGroup === group.id ? null : group.id"
                                                class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                                            <IconPlus :size="16" /> Tambah Soal
                                        </button>
                                        <button @click="importGroup = importGroup === group.id ? null : group.id"
                                                class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                                            <IconUpload :size="16" /> Import
                                        </button>
                                    </div>

                                    <div v-if="editingGroup === group.id" class="mt-4 border-t border-outline-variant/30 pt-4">
                                        <QuestionEditor :group-id="group.id" @saved="editingGroup = null" />
                                    </div>
                                    <div v-if="importGroup === group.id" class="mt-4 border-t border-outline-variant/30 pt-4">
                                        <BulkQuestionImport :group-id="group.id" @saved="importGroup = null" />
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-text-muted text-body-md">Belum ada grup soal. Buat grup untuk mulai menambahkan soal.</p>
                            </div>

                            <form @submit.prevent="addGroup(section.id)" class="border-t border-outline-variant/30 pt-4">
                                <p class="text-label-md font-medium text-primary mb-3">Tambah Grup Soal Baru</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <input type="text" v-model="groupForm.title" placeholder="Nama grup (mis: Passage 1)"
                                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                                    <div class="flex gap-2">
                                        <input type="number" v-model="groupForm.order" placeholder="Urutan" min="1"
                                               class="w-24 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                                        <button type="submit" :disabled="groupForm.processing"
                                                class="bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                                            {{ groupForm.processing ? '...' : 'Tambah' }}
                                        </button>
                                    </div>
                                </div>
                                <textarea v-model="groupForm.passage_text" rows="3" placeholder="Passage text (untuk Reading section)"
                                          class="mt-3 w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"></textarea>
                            </form>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10">
                    <p class="text-text-muted text-body-md">Belum ada section.</p>
                </div>

                <div class="border-t border-outline-variant/30 pt-4 mt-4">
                    <form @submit.prevent="addSection" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <input type="text" v-model="sectionForm.title" required placeholder="Nama section"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <select v-model="sectionForm.type"
                                class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                            <option value="reading">Reading</option>
                            <option value="listening">Listening</option>
                            <option value="speaking">Speaking</option>
                            <option value="writing">Writing</option>
                        </select>
                        <input type="number" v-model="sectionForm.order" placeholder="Urutan" min="1"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <button type="submit" :disabled="sectionForm.processing"
                                class="flex items-center justify-center gap-2 bg-primary-container text-white py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                            <IconPlus :size="18" /> Tambah Section
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
