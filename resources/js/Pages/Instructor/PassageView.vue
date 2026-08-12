<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import FileUpload from '@/Components/Shared/FileUpload.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import QuickAddQuestionForm from '@/Components/ContentLibrary/QuickAddQuestionForm.vue';
import PassageMediaViewer from '@/Components/ContentLibrary/PassageMediaViewer.vue';
import { useUploadProgress } from '@/Composables/useUploadProgress';
import { IconPlus, IconEdit, IconTrash, IconFileDescription, IconHeadphones, IconPhoto, IconBook, IconX, IconBooks } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const confirm = useConfirm();

const props = defineProps({
    passages: { type: Object, default: () => ({ data: [] }) },
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
});

const showForm = ref(false);
const editingPassage = ref(null);
const addingPassageId = ref(null);

const optionKeys = ['A', 'B', 'C', 'D'];

const quickQuestionForm = useForm({
    passage_id: null,
    question_bank_id: '',
    skill_id: '',
    skill_part_id: '',
    material_type: 'text',
    question_text: '',
    option_a: '',
    option_b: '',
    option_c: '',
    option_d: '',
    correct_answer: '',
});

const quickSelectedBank = computed(() =>
    props.questionBanks.find(b => String(b.id) === String(quickQuestionForm.question_bank_id)) || null,
);

const availableQuickSkills = computed(() => {
    if (!quickSelectedBank.value) return props.skills;
    return props.skills.filter(s => String(s.exam_type_id) === String(quickSelectedBank.value.exam_type_id));
});

const quickIsAudio = computed(() => (quickQuestionForm.material_type || 'text') === 'audio');

function quickParts() {
    return props.parts.filter(p => String(p.skill_id) === String(quickQuestionForm.skill_id));
}

function onQuickSkillChange() {
    quickQuestionForm.skill_part_id = '';
}

const form = useForm({
    title: '',
    type: 'text',
    content_text: '',
    audio_file: null,
    image_file: null,
});

const typeLabels = {
    text: 'Teks (Reading)', audio: 'Audio (Listening)', image: 'Gambar (Photo)', prompt: 'Prompt (Speaking/Writing)',
};

function openCreate() {
    editingPassage.value = null;
    form.reset();
    form.type = 'text';
    showForm.value = true;
}

function openEdit(passage) {
    editingPassage.value = passage;
    form.title = passage.title;
    form.type = passage.type || 'text';
    form.content_text = passage.content_text || '';
    form.audio_file = null;
    form.image_file = null;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingPassage.value = null;
}

const storedAudioPreview = computed(() => {
    if (form.audio_file) return null;
    return editingPassage.value?.audio_url ? '/storage/' + editingPassage.value.audio_url : null;
});

const storedImagePreview = computed(() => {
    if (form.image_file) return null;
    return editingPassage.value?.image_url ? '/storage/' + editingPassage.value.image_url : null;
});

watch(() => form.type, () => {
    form.audio_file = null;
    form.image_file = null;
});

function submit() {
    form.transform(data => ({
        ...data,
        audio_file: data.audio_file || undefined,
        image_file: data.image_file || undefined,
    }));

    if (editingPassage.value) {
        form.put(route('content-library.passages.update', editingPassage.value.id), {
            preserveScroll: true,
            onSuccess: closeForm,
        });
    } else {
        form.post(route('content-library.passages.store'), {
            preserveScroll: true,
            onSuccess: closeForm,
        });
    }
}

const { showUploadProgress, uploadLabel, mediaType } = useUploadProgress(form);

function openQuickAdd(passage) {
    addingPassageId.value = passage.id;
    quickQuestionForm.clearErrors();
    quickQuestionForm.reset();
    quickQuestionForm.passage_id = passage.id;
    quickQuestionForm.material_type =
        passage.audio_url || passage.type === 'audio' ? 'audio' : 'text';
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
                material_type: data.material_type,
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
                router.reload({ only: ['passages'], preserveState: true, preserveScroll: true });
            },
        });
}

async function deletePassage(id, title) {
    if (!await confirm.confirm(`Hapus materi soal: "${title}"?`)) return;
    router.delete(route('content-library.passages.destroy', id), { preserveScroll: true });
}

function getMediaIcon(passage) {
    if (passage.type === 'audio' || passage.audio_url) return 'audio';
    if (passage.type === 'image' || passage.image_url) return 'image';
    if (passage.type === 'prompt') return 'prompt';
    return 'text';
}
</script>

<template>
    <Head title="Materi Soal" />
    <DashboardLayout title="Kelola Materi Soal">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <Link :href="route('content-library.index')" class="text-secondary text-label-md font-medium hover:underline">← Kembali ke Bank Soal</Link>
                <p class="text-text-body text-body-md">{{ passages.total || 0 }} materi soal</p>
            </div>
            <BaseButton @click="openCreate">
                <IconPlus :size="18" /> Materi Soal Baru
            </BaseButton>
        </div>

        <EmptyState
            v-if="passages.data?.length === 0"
            :icon="IconBook"
            title="Belum ada materi soal."
        >
            <BaseButton @click="openCreate" class="mt-4">
                <IconPlus :size="16" /> Buat Materi Soal Pertama
            </BaseButton>
        </EmptyState>

        <div v-else class="space-y-3">
            <BaseCard
                v-for="p in passages.data"
                :key="p.id"
                padding="p-5"
                class="hover:border-secondary/50 transition-all"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl shrink-0 mt-1"
                              :class="getMediaIcon(p) === 'audio' ? 'bg-pastel-purple/30' : getMediaIcon(p) === 'image' ? 'bg-pastel-peach/30' : getMediaIcon(p) === 'prompt' ? 'bg-pastel-blue/30' : 'bg-surface-container-low'">
                            <IconHeadphones v-if="getMediaIcon(p) === 'audio'" :size="18" class="text-primary" />
                            <IconPhoto v-else-if="getMediaIcon(p) === 'image'" :size="18" class="text-primary" />
                            <IconFileDescription v-else-if="getMediaIcon(p) === 'prompt'" :size="18" class="text-primary" />
                            <IconBook v-else :size="18" class="text-text-muted" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-body-md font-semibold text-primary">{{ p.title }}</h3>
                                <span class="inline-block bg-pastel-purple/30 text-primary px-2 py-0.5 rounded-full text-label-md">{{ typeLabels[p.type] || 'Teks' }}</span>
                            </div>
                            <PassageMediaViewer v-if="p.content_text || p.audio_url || p.image_url" :passage="p" variant="compact" />
                            <p class="text-label-md text-text-muted mt-1">
                                {{ p.questions_count || 0 }} soal
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <BaseButton :href="route('content-library.index', { passage_id: p.id })" variant="secondary" size="sm" class="px-3"
                              title="Lihat Soal">
                            <IconBooks :size="16" /> {{ p.questions_count || 0 }} Soal
                        </BaseButton>
                        <BaseButton @click="addingPassageId === p.id ? closeQuickAdd() : openQuickAdd(p)" size="sm"
                                title="Tambah soal ke materi soal ini">
                            <IconPlus :size="16" /> Tambah Soal
                        </BaseButton>
                        <button @click="openEdit(p)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit">
                            <IconEdit :size="18" />
                        </button>
                        <button @click="deletePassage(p.id, p.title)"
                                :disabled="(p.questions_count || 0) > 0"
                                :title="(p.questions_count || 0) > 0 ? `Tidak bisa dihapus — materi soal dipakai ${p.questions_count} soal` : 'Hapus'"
                                class="p-2 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted">
                            <IconTrash :size="18" />
                        </button>
                    </div>
                </div>

                <!-- QUICK ADD SOAL KE PASSAGE -->
                <QuickAddQuestionForm
                    v-if="addingPassageId === p.id"
                    :form="quickQuestionForm"
                    :passage-title="p.title"
                    :question-banks="questionBanks"
                    :available-quick-skills="availableQuickSkills"
                    :quick-parts-fn="quickParts"
                    :quick-is-audio="quickIsAudio"
                    :option-keys="optionKeys"
                    class="mt-4"
                    @save="saveQuickQuestion"
                    @cancel="closeQuickAdd"
                    @skill-change="onQuickSkillChange"
                />
            </BaseCard>
        </div>

        <Pagination
            :links="passages.links"
            :total="passages.total"
            :per-page="passages.per_page"
        />

        <!-- Modal Form -->
        <Modal :show="showForm" max-width="2xl" scrollable @close="closeForm">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ editingPassage ? 'Edit Materi Soal' : 'Materi Soal Baru' }}</h2>
                    <button @click="closeForm" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Judul *</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Email: Staff Meeting Reminder / Percakapan di Kafe / ..."
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.title" class="text-error-red text-xs mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <DropDown
                            v-model="form.type"
                            :options="Object.entries(typeLabels).map(([id, name]) => ({ id, name }))"
                            label="Tipe Materi Soal *"
                            option-label="name"
                            option-value="id"
                        />
                    </div>

                    <!-- TEXT / PROMPT: content_text -->
                    <div v-if="form.type === 'text' || form.type === 'prompt'">
                        <label class="text-label-md font-medium text-primary block mb-1.5">
                            {{ form.type === 'prompt' ? 'Teks Prompt' : 'Teks Bacaan' }}
                            <span class="text-text-muted">{{ form.type === 'prompt' ? '(Speaking/Writing)' : '(Reading)' }}</span>
                        </label>
                        <RichTextEditor v-model="form.content_text"
                                        :placeholder="form.type === 'prompt' ? 'Tulis instruksi atau prompt...' : 'Tulis isi bacaan lengkap di sini...'" />
                        <p v-if="form.errors.content_text" class="text-error-red text-xs mt-1">{{ form.errors.content_text }}</p>
                    </div>

                    <!-- AUDIO: file upload -->
                    <div v-if="form.type === 'audio'">
                        <FileUpload
                            v-model="form.audio_file"
                            label="Audio"
                            placeholder="Klik untuk upload file audio"
                            accept="audio/*"
                            hint="MP3, WAV, OGG, M4A (max 50MB)"
                            variant="dropzone"
                            media-type="audio"
                            :preview-url="storedAudioPreview"
                            :error="form.errors.audio_file"
                        />
                    </div>

                    <!-- IMAGE: file upload -->
                    <div v-if="form.type === 'image'">
                        <FileUpload
                            v-model="form.image_file"
                            label="Gambar"
                            placeholder="Klik untuk upload gambar"
                            accept="image/*"
                            hint="JPG, PNG, WebP (max 20MB, otomatis dikompres)"
                            variant="dropzone"
                            media-type="image"
                            :preview-url="storedImagePreview"
                            :error="form.errors.image_file"
                        />
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="form.processing" size="xl" class="flex-1">
                            {{ form.processing ? 'Menyimpan...' : (editingPassage ? 'Simpan Perubahan' : 'Buat Materi Soal') }}
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" size="lg" @click="closeForm">
                            Batal
                        </BaseButton>
                    </div>
                </form>
            </div>
        </Modal>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" :media-type="mediaType" />
    </DashboardLayout>
</template>
