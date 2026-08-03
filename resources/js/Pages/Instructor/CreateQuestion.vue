<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import { IconPlus, IconTrash, IconFileDescription, IconUpload } from '@tabler/icons-vue';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const passageMode = ref('none');
const passageType = ref('text');
const questionRefs = ref([]);

function newQuestion() {
    return {
        skill_id: '',
        question_text: '',
        option_a: '',
        option_b: '',
        option_c: '',
        option_d: '',
        correct_answer: '',
    };
}

const form = useForm({
    question_bank_id: '',
    new_passage_title: '',
    new_passage_type: 'text',
    new_passage_content_text: '',
    new_passage_audio_file: null,
    new_passage_image_file: null,
    questions: [newQuestion()],
});

async function addQuestion() {
    form.questions.push(newQuestion());
    await nextTick();
    const last = questionRefs.value[form.questions.length - 1];
    last?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removeQuestion(index) {
    if (form.questions.length > 1) {
        form.questions.splice(index, 1);
    }
}

function onPassageFile(e, field) {
    form[field] = e.target.files[0] || null;
    e.target.value = '';
}

const selectedAudioFile = ref(null);
const selectedImageFile = ref(null);

function onAudioSelect(e) {
    selectedAudioFile.value = e.target.files[0] || null;
    onPassageFile(e, 'new_passage_audio_file');
}

function onImageSelect(e) {
    selectedImageFile.value = e.target.files[0] || null;
    onPassageFile(e, 'new_passage_image_file');
}

const canSubmit = computed(() => {
    if (!form.question_bank_id) return false;
    if (form.questions.length === 0) return false;
    if (passageMode.value === 'new' && !form.new_passage_title.trim()) return false;
    return form.questions.every(q =>
        q.skill_id &&
        q.question_text.trim() &&
        q.option_a.trim() && q.option_b.trim() &&
        q.option_c.trim() && q.option_d.trim() &&
        q.correct_answer
    );
});

function submit() {
    form.post(route('content-library.store'));
}

const showUploadProgress = computed(() => form.processing && (form.new_passage_audio_file !== null || form.new_passage_image_file !== null));
const uploadLabel = computed(() => form.new_passage_audio_file !== null ? 'Mengunggah & mengompres audio...' : 'Mengunggah & mengompres gambar...');
</script>

<template>
    <Head title="Tambah Soal Baru" />
    <DashboardLayout title="Tambah Soal Baru">
        <Link :href="route('content-library.index')" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Content Library</Link>
        <div class="max-w-5xl mx-auto">
            <div class="bg-surface-white rounded-3xl p-8 shadow-soft border border-outline-variant/30">
                <h2 class="text-headline-md font-bold text-primary mb-6">Tambah Soal Baru</h2>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Bank Soal <span class="text-error-red">*</span></label>
                            <select v-model="form.question_bank_id" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih Bank Soal</option><option v-for="b in questionBanks" :key="b.id" :value="b.id">{{ b.name }}</option></select>
                            <p v-if="form.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ form.errors.question_bank_id }}</p></div>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Status <span class="text-text-muted">(otomatis Draf)</span></label>
                            <div class="px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md"><span class="inline-block bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300 px-3 py-1 rounded-full text-label-md font-medium">Draf</span></div></div>
                    </div>

                    <hr class="border-outline-variant/50" />

                    <!-- MODE PASSAGE -->
                    <div>
                        <p class="text-label-md font-medium text-primary mb-2">Materi Soal <span class="text-text-muted">(opsional — bisa tanpa materi soal)</span></p>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                                   :class="passageMode === 'none' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                                <input type="radio" value="none" v-model="passageMode" class="accent-secondary" />
                                <span class="text-label-md text-text-body">Tanpa Materi Soal</span>
                            </label>
                            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                                   :class="passageMode === 'new' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                                <input type="radio" value="new" v-model="passageMode" class="accent-secondary" />
                                <span class="text-label-md text-text-body">Buat Materi Soal Baru</span>
                            </label>
                        </div>
                    </div>

                    <!-- FORM PASSAGE INLINE -->
                    <div v-if="passageMode === 'new'" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2">
                            <IconFileDescription :size="18" class="text-secondary" />
                            <p class="text-label-md font-semibold text-primary">Materi Soal Baru</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Judul Materi Soal <span class="text-error-red">*</span></label>
                                <input type="text" v-model="form.new_passage_title" required placeholder="Judul bacaan"
                                       class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                <p v-if="form.errors.new_passage_title" class="text-error-red text-xs mt-1">{{ form.errors.new_passage_title }}</p></div>
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Tipe Materi Soal <span class="text-error-red">*</span></label>
                                <select v-model="passageType" @change="form.new_passage_type = passageType" class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                                    <option value="text">Teks</option>
                                    <option value="audio">Audio</option>
                                    <option value="image">Gambar</option>
                                    <option value="prompt">Prompt</option>
                                </select></div>
                        </div>

                        <div v-if="passageType === 'text' || passageType === 'prompt'">
                            <label class="text-label-md font-medium text-primary block mb-1.5">Konten Teks</label>
                            <RichTextEditor v-model="form.new_passage_content_text" placeholder="Tulis isi teks bacaan di sini..." />
                        </div>

                        <div v-else-if="passageType === 'audio'">
                            <label class="text-label-md font-medium text-primary block mb-1.5">File Audio <span class="text-text-muted">(mp3/wav/m4a, max 50MB)</span></label>
                            <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-2xl px-5 py-4 cursor-pointer hover:border-secondary transition-colors">
                                <IconUpload :size="20" class="text-text-muted" />
                                <span class="text-label-md text-text-body">{{ selectedAudioFile ? selectedAudioFile.name : 'Klik untuk upload audio' }}</span>
                                <input type="file" accept=".mp3,.wav,.ogg,.m4a" class="hidden" @change="onAudioSelect" />
                            </label>
                            <p v-if="form.errors.new_passage_audio_file" class="text-error-red text-xs mt-1">{{ form.errors.new_passage_audio_file }}</p>
                        </div>

                        <div v-else-if="passageType === 'image'">
                            <label class="text-label-md font-medium text-primary block mb-1.5">File Gambar <span class="text-text-muted">(jpg/png/webp, max 20MB, otomatis dikompres)</span></label>
                            <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-2xl px-5 py-4 cursor-pointer hover:border-secondary transition-colors">
                                <IconUpload :size="20" class="text-text-muted" />
                                <span class="text-label-md text-text-body">{{ selectedImageFile ? selectedImageFile.name : 'Klik untuk upload gambar' }}</span>
                                <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onImageSelect" />
                            </label>
                            <p v-if="form.errors.new_passage_image_file" class="text-error-red text-xs mt-1">{{ form.errors.new_passage_image_file }}</p>
                        </div>
                    </div>

                    <hr class="border-outline-variant/50" />

                    <!-- DAFTAR SOAL -->
                    <div class="flex items-center justify-between">
                        <p class="text-title-lg font-semibold text-primary">Soal</p>
                        <button type="button" @click="addQuestion"
                                class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                            <IconPlus :size="18" /> Tambah Soal
                        </button>
                    </div>

                    <div v-for="(q, qi) in form.questions" :key="qi"
                         :ref="el => questionRefs[qi] = el"
                         class="border border-outline-variant/50 rounded-2xl p-5 space-y-4 relative">
                        <div class="flex items-center justify-between">
                            <p class="text-label-md font-semibold text-primary">Soal {{ qi + 1 }}</p>
                            <button v-if="form.questions.length > 1" type="button" @click="removeQuestion(qi)"
                                    class="p-1.5 text-text-muted hover:text-error-red transition-colors" title="Hapus soal">
                                <IconTrash :size="16" />
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Skill <span class="text-error-red">*</span></label>
                                <select v-model="q.skill_id" required class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih Skill</option><option v-for="s in skills" :key="s.id" :value="s.id">{{ s.name }}</option></select></div>
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Kunci Jawaban <span class="text-error-red">*</span></label>
                                <select v-model="q.correct_answer" required class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                                    <option value="" disabled>Pilih jawaban benar</option>
                                    <option v-for="key in optionKeys" :key="key" :value="key">{{ key }}</option>
                                </select></div>
                        </div>

                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                            <textarea v-model="q.question_text" rows="2" required class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"></textarea></div>

                        <div>
                            <p class="text-label-md font-medium text-primary mb-2">Pilihan Jawaban <span class="text-error-red">*</span></p>
                            <div class="space-y-2">
                                <div v-for="key in optionKeys" :key="key" class="flex items-center gap-3">
                                    <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-surface-container-low border border-outline-variant font-semibold text-primary">{{ key }}</span>
                                    <input type="text" v-model="q['option_' + key.toLowerCase()]" required :placeholder="'Teks pilihan ' + key"
                                           class="flex-1 px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <button type="submit" :disabled="form.processing || !canSubmit" class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : `Simpan ${form.questions.length} Soal` }}
                        </button>
                        <Link :href="route('content-library.index')" class="px-8 py-3.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Batal</Link>
                    </div>
                </form>
            </div>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" />
    </DashboardLayout>
</template>
