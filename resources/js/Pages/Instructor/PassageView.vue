<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import QuickAddQuestionForm from '@/Components/ContentLibrary/QuickAddQuestionForm.vue';
import { IconPlus, IconEdit, IconTrash, IconFileDescription, IconHeadphones, IconPhoto, IconBook, IconX, IconBooks, IconUpload } from '@tabler/icons-vue';
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
const audioPreviewUrl = ref(null);
const imagePreviewUrl = ref(null);
const audioFileInput = ref(null);
const imageFileInput = ref(null);
const addingPassageId = ref(null);

const optionKeys = ['A', 'B', 'C', 'D'];

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
    audioPreviewUrl.value = null;
    imagePreviewUrl.value = null;
    showForm.value = true;
}

function openEdit(passage) {
    editingPassage.value = passage;
    form.title = passage.title;
    form.type = passage.type || 'text';
    form.content_text = passage.content_text || '';
    form.audio_file = null;
    form.image_file = null;
    audioPreviewUrl.value = passage.audio_url ? '/storage/' + passage.audio_url : null;
    imagePreviewUrl.value = passage.image_url ? '/storage/' + passage.image_url : null;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingPassage.value = null;
}

function onAudioFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.audio_file = file;
    audioPreviewUrl.value = URL.createObjectURL(file);
}

function onImageFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.image_file = file;
    imagePreviewUrl.value = URL.createObjectURL(file);
}

function removeAudio() {
    form.audio_file = null;
    audioPreviewUrl.value = null;
    if (audioFileInput.value) audioFileInput.value.value = '';
}

function removeImage() {
    form.image_file = null;
    imagePreviewUrl.value = null;
    if (imageFileInput.value) imageFileInput.value.value = '';
}

watch(() => form.type, () => {
    form.audio_file = null;
    form.image_file = null;
    audioPreviewUrl.value = null;
    imagePreviewUrl.value = null;
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

const showUploadProgress = computed(() => form.processing && (form.audio_file !== null || form.image_file !== null));
const uploadLabel = computed(() => form.audio_file !== null ? 'Mengunggah & mengompres audio...' : 'Mengunggah & mengompres gambar...');

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

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

function previewText(text, max) {
    if (!text) return '';
    const plain = stripHtml(text);
    return plain.length > max ? plain.substring(0, max) + '...' : plain;
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
            <button @click="openCreate"
                    class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                <IconPlus :size="18" /> Materi Soal Baru
            </button>
        </div>

        <div v-if="passages.data?.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <IconBook class="mx-auto text-text-muted mb-3" :size="48" stroke="1.5" />
            <p class="text-text-body text-body-md">Belum ada materi soal.</p>
            <button @click="openCreate" class="mt-4 inline-flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all">
                <IconPlus :size="16" /> Buat Materi Soal Pertama
            </button>
        </div>

        <div v-else class="space-y-3">
            <div v-for="p in passages.data" :key="p.id"
                 class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 hover:border-secondary/50 transition-all">
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
                            <p v-if="p.content_text" class="text-label-md text-text-body mb-2 line-clamp-2">{{ previewText(p.content_text, 120) }}</p>
                            <template v-else-if="p.audio_url">
                                <div class="flex items-center gap-2 mb-1">
                                    <IconHeadphones :size="14" class="text-text-muted" />
                                    <audio :src="'/storage/' + p.audio_url" controls class="h-8 w-full max-w-xs" preload="none"></audio>
                                </div>
                            </template>
                            <template v-else-if="p.image_url">
                                <div class="flex items-center gap-2 mb-1">
                                    <IconPhoto :size="14" class="text-text-muted" />
                                    <img :src="'/storage/' + p.image_url" class="h-12 rounded-lg object-cover" :alt="p.title" />
                                </div>
                            </template>
                            <p class="text-label-md text-text-muted mt-1">
                                {{ p.questions_count || 0 }} soal
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <Link :href="route('content-library.index', { passage_id: p.id })"
                              class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:border-secondary transition-all"
                              title="Lihat Soal">
                            <IconBooks :size="16" /> {{ p.questions_count || 0 }} Soal
                        </Link>
                        <button @click="addingPassageId === p.id ? closeQuickAdd() : openQuickAdd(p)"
                                class="flex items-center gap-1.5 px-3 py-2 bg-primary-container text-white rounded-full text-label-md font-medium hover:bg-primary transition-all"
                                title="Tambah soal ke materi soal ini">
                            <IconPlus :size="16" /> Tambah Soal
                        </button>
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
                    :quick-is-listening="quickIsListening"
                    :option-keys="optionKeys"
                    class="mt-4"
                    @save="saveQuickQuestion"
                    @cancel="closeQuickAdd"
                    @skill-change="onQuickSkillChange"
                />
            </div>
        </div>

        <div v-if="passages.total > passages.per_page" class="flex justify-center mt-6 gap-2">
            <Link v-for="link in passages.links" :key="link.label"
                  :href="link.url || '#'"
                  class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                  :class="link.active ? 'bg-primary-container text-white' : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                  v-html="link.label" />
        </div>

        <!-- Modal Form -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-8 shadow-soft w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
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
                        <label class="text-label-md font-medium text-primary block mb-1.5">Audio</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-secondary transition-colors cursor-pointer"
                             :class="{ 'border-error-red': form.errors.audio_file }"
                             @click="audioFileInput?.click()">
                            <IconUpload class="mx-auto text-text-muted mb-2" :size="24" stroke="1.5" />
                            <p class="text-label-md text-text-body font-medium">
                                {{ form.audio_file ? form.audio_file.name : 'Klik untuk upload file audio' }}
                            </p>
                            <p class="text-xs text-text-muted mt-1">MP3, WAV, OGG, M4A (max 50MB)</p>
                        </div>
                        <p v-if="form.errors.audio_file" class="text-error-red text-xs mt-1">{{ form.errors.audio_file }}</p>
                        <input ref="audioFileInput" type="file" accept="audio/*" @change="onAudioFileChange" class="hidden" />

                        <div v-if="audioPreviewUrl" class="relative mt-3 bg-surface-container-low rounded-xl p-3">
                            <audio :src="audioPreviewUrl" controls class="w-full h-10" preload="metadata"></audio>
                            <button type="button" @click="removeAudio"
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-error-red text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors shadow-sm"
                                    title="Hapus audio">
                                <IconX :size="14" />
                            </button>
                        </div>
                    </div>

                    <!-- IMAGE: file upload -->
                    <div v-if="form.type === 'image'">
                        <label class="text-label-md font-medium text-primary block mb-1.5">Gambar</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-secondary transition-colors cursor-pointer"
                             :class="{ 'border-error-red': form.errors.image_file }"
                             @click="imageFileInput?.click()">
                            <IconUpload class="mx-auto text-text-muted mb-2" :size="24" stroke="1.5" />
                            <p class="text-label-md text-text-body font-medium">
                                {{ form.image_file ? form.image_file.name : 'Klik untuk upload gambar' }}
                            </p>
                            <p class="text-xs text-text-muted mt-1">JPG, PNG, WebP (max 20MB, otomatis dikompres)</p>
                        </div>
                        <p v-if="form.errors.image_file" class="text-error-red text-xs mt-1">{{ form.errors.image_file }}</p>
                        <input ref="imageFileInput" type="file" accept="image/*" @change="onImageFileChange" class="hidden" />

                        <div v-if="imagePreviewUrl" class="relative mt-3 bg-surface-container-low rounded-xl p-3">
                            <img :src="imagePreviewUrl" class="max-h-40 rounded-lg object-contain mx-auto" :alt="form.title" />
                            <button type="button" @click="removeImage"
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-error-red text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors shadow-sm"
                                    title="Hapus gambar">
                                <IconX :size="14" />
                            </button>
                        </div>
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <button type="submit" :disabled="form.processing"
                                class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : (editingPassage ? 'Simpan Perubahan' : 'Buat Materi Soal') }}
                        </button>
                        <button type="button" @click="closeForm"
                                class="px-8 py-3.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" />
    </DashboardLayout>
</template>
