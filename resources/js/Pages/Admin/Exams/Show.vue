<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import QuestionEditor from '@/Components/Admin/QuestionEditor.vue';
import BulkQuestionImport from '@/Components/Admin/BulkQuestionImport.vue';
import { IconPlus, IconTrash, IconEdit, IconChevronDown, IconChevronRight, IconFileDescription } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const confirm = useConfirm();

const props = defineProps({
    exam: { type: Object, required: true },
    skills: { type: Array, default: () => [] },
});

const expandedSections = ref({});
const showQuestionEditor = ref(false);
const showImportModal = ref(false);

function toggleSection(id) {
    expandedSections.value[id] = !expandedSections.value[id];
}

const sectionForm = useForm({
    title: '',
    skill_id: '',
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

async function deleteQuestion(questionId) {
    if (await confirm.confirm('Hapus soal ini?')) {
        const form = useForm({});
        form.delete(route('admin.exams.questions.destroy', questionId), { preserveScroll: true });
    }
}

async function deleteSection(sectionId) {
    if (await confirm.confirm('Hapus section ini?')) {
        const form = useForm({});
        form.delete(route('admin.exams.sections.destroy', [props.exam.id, sectionId]), { preserveScroll: true });
    }
}

function skillName(id) {
    return props.skills.find(s => s.id === id)?.name || '';
}

const skillIcons = { reading: '📖', listening: '🎧', speaking: '🎤', writing: '✍️' };
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

            <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
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
                                <span>{{ skillIcons[skillName(section.skill_id).toLowerCase()] || '📄' }}</span>
                                <div>
                                    <p class="font-semibold text-primary">{{ section.title }}</p>
                                    <p class="text-text-muted text-label-md">{{ skillName(section.skill_id) }} — {{ section.total_questions }} soal</p>
                                </div>
                            </div>
                            <button @click.stop="deleteSection(section.id)"
                                    class="p-2 text-text-muted hover:text-error-red transition-colors">
                                <IconTrash :size="18" />
                            </button>
                        </div>

                        <div v-if="expandedSections[section.id]" class="p-4 border-t border-outline-variant/30">
                            <p class="text-text-muted text-label-md text-center py-6">
                                Soal untuk section ini akan dikelola melalui fitur Test Form.
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10">
                    <p class="text-text-muted text-text-body text-body-md">Belum ada section.</p>
                </div>

                <div class="border-t border-outline-variant/30 pt-4 mt-4">
                    <form @submit.prevent="addSection" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <input type="text" v-model="sectionForm.title" required placeholder="Nama section"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <select v-model="sectionForm.skill_id" required
                                class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                            <option value="" disabled>Pilih Skill</option>
                            <option v-for="s in skills" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <input type="number" v-model="sectionForm.order" placeholder="Urutan" min="1"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
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
