<script setup>
import { Link } from '@inertiajs/vue3';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import QuestionTable from '@/Components/ContentLibrary/QuestionTable.vue';
import QuickAddQuestionForm from '@/Components/ContentLibrary/QuickAddQuestionForm.vue';
import { IconFileDescription, IconEye, IconPlus, IconEdit, IconTrash, IconUpload, IconCheck } from '@tabler/icons-vue';

defineProps({
    group: { type: Object, required: true },
    canReview: { type: Boolean, default: false },
    passageForm: { type: Object, required: true },
    editingPassageId: { type: [Number, String, null], default: null },
    passageEditType: { type: String, default: 'text' },
    selectedAudioFile: { type: Object, default: null },
    selectedImageFile: { type: Object, default: null },
    selectedIds: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
    statusColors: { type: Object, default: () => ({}) },
    skillNameFn: { type: Function, default: () => '' },
    bankNameFn: { type: Function, default: () => '' },
    optionKeys: { type: Array, default: () => [] },
    quickForm: { type: Object, required: true },
    questionBanks: { type: Array, default: () => [] },
    addingPassageId: { type: [Number, String, null], default: null },
    quickSkills: { type: Array, default: () => [] },
    quickPartsFn: { type: Function, default: () => [] },
    quickIsListening: { type: Boolean, default: false },
    isPassageSelectedFn: { type: Function, default: () => false },
    togglePassageSelectionFn: { type: Function, default: () => {} },
    togglePassageFn: { type: Function, default: () => {} },
    passageTypeLabelFn: { type: Function, default: (t) => t || 'Teks' },
    isPassageExpandedFn: { type: Function, default: () => false },
});

defineEmits([
    'start-edit',
    'cancel-edit',
    'save-passage',
    'audio-select',
    'image-select',
    'add-soal',
    'close-add',
    'save-quick',
    'quick-skill-change',
    'question-toggle',
    'question-review',
    'question-delete',
    'delete-passage',
]);

const passageTypeOptions = [
    { id: 'text', name: 'Teks' },
    { id: 'audio', name: 'Audio' },
    { id: 'image', name: 'Gambar' },
    { id: 'prompt', name: 'Prompt' },
];
</script>

<template>
    <div class="bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden">
        <div class="px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/30">
            <!-- TAMPILAN HEADER NORMAL -->
            <template v-if="editingPassageId !== group.passage.id">
                <div class="flex items-center gap-2 flex-wrap">
                    <input v-if="canReview && group.questions.length" type="checkbox"
                           :checked="isPassageSelectedFn(group)" @change="togglePassageSelectionFn(group)"
                           class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary shrink-0"
                           title="Pilih semua soal di materi ini" />
                    <IconFileDescription :size="20" class="text-secondary shrink-0" />
                    <h3 class="text-title-md font-semibold text-primary">{{ group.passage.title }}</h3>
                    <span class="inline-block bg-pastel-purple/50 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">
                        {{ passageTypeLabelFn(group.passage.type) }}
                    </span>
                    <span class="text-label-md text-text-muted">{{ group.questions.length }} soal</span>
                    <div class="ml-auto flex items-center gap-1">
                        <Link v-if="group.questions.length" :href="route('content-library.preview', group.questions[0].id)"
                              class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Preview soal materi ini">
                            <IconEye :size="16" />
                        </Link>
                        <button @click="$emit('add-soal', group.passage)"
                                class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-label-md font-medium text-secondary hover:bg-surface-container-highest transition-colors"
                                title="Tambah soal ke materi soal ini">
                            <IconPlus :size="16" /> Soal
                        </button>
                        <button @click="$emit('start-edit', group.passage)" class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Edit passage">
                            <IconEdit :size="16" />
                        </button>
                        <button @click="$emit('delete-passage', group.passage)"
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
                                    :clamp="isPassageExpandedFn(group.passage.id) ? 0 : 3"
                                    class="mt-2" />
                    <button v-if="group.passage.content_text.length > 180" @click="togglePassageFn(group.passage.id)"
                            class="mt-1 text-secondary text-label-md font-medium hover:underline">
                        {{ isPassageExpandedFn(group.passage.id) ? 'Sembunyikan ▲' : 'Lihat Selengkapnya ▼' }}
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
                        <DropDown
                            v-model="passageForm.type"
                            :options="passageTypeOptions"
                            label="Tipe Materi Soal"
                            option-label="name"
                            option-value="id"
                            size="sm"
                            @change="passageEditType = $event"
                        />
                    </div>
                </div>

                <div v-if="passageEditType === 'text' || passageEditType === 'prompt'" class="mt-3">
                    <label class="text-label-md font-medium text-primary block mb-1">Konten Teks</label>
                    <RichTextEditor v-model="passageForm.content_text" placeholder="Isi teks passage..." />
                </div>

                <div v-else-if="passageEditType === 'audio'" class="mt-3">
                    <label class="text-label-md font-medium text-primary block mb-1">
                        File Audio <span class="text-text-muted">(mp3/wav/m4a, max 50MB)</span>
                    </label>
                    <audio v-if="group.passage.audio_url" controls :src="'/storage/' + group.passage.audio_url" class="w-full max-w-md h-9 mb-2"></audio>
                    <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors">
                        <IconUpload :size="18" class="text-text-muted" />
                        <span class="text-label-md text-text-body">{{ selectedAudioFile ? selectedAudioFile.name : (group.passage.audio_url ? 'Ganti file audio' : 'Upload audio') }}</span>
                        <input type="file" accept=".mp3,.wav,.ogg,.m4a" class="hidden" @change="$emit('audio-select', $event)" />
                    </label>
                    <p v-if="passageForm.errors.audio_file" class="text-error-red text-xs mt-1">{{ passageForm.errors.audio_file }}</p>
                </div>

                <div v-else-if="passageEditType === 'image'" class="mt-3">
                    <label class="text-label-md font-medium text-primary block mb-1">
                        File Gambar <span class="text-text-muted">(jpg/png/webp, max 20MB, otomatis dikompres)</span>
                    </label>
                    <img v-if="group.passage.image_url" :src="'/storage/' + group.passage.image_url"
                         class="max-h-32 rounded-lg border border-outline-variant/30 object-contain mb-2" />
                    <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors">
                        <IconUpload :size="18" class="text-text-muted" />
                        <span class="text-label-md text-text-body">{{ selectedImageFile ? selectedImageFile.name : (group.passage.image_url ? 'Ganti file gambar' : 'Upload gambar') }}</span>
                        <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="$emit('image-select', $event)" />
                    </label>
                    <p v-if="passageForm.errors.image_file" class="text-error-red text-xs mt-1">{{ passageForm.errors.image_file }}</p>
                </div>

                <div class="flex items-center gap-2 mt-4">
                    <button @click="$emit('save-passage', group.passage)" :disabled="passageForm.processing"
                            class="flex items-center gap-1.5 bg-primary-container text-white px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        <IconCheck :size="16" /> {{ passageForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <button @click="$emit('cancel-edit')"
                            class="flex items-center gap-1.5 px-5 py-2.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                        Batal
                    </button>
                </div>
            </template>
        </div>

        <!-- QUICK ADD SOAL KE PASSAGE -->
        <QuickAddQuestionForm
            v-if="addingPassageId === group.passage.id"
            :form="quickForm"
            :passage-title="group.passage.title"
            :question-banks="questionBanks"
            :available-quick-skills="quickSkills"
            :quick-parts-fn="quickPartsFn"
            :quick-is-listening="quickIsListening"
            :option-keys="optionKeys"
            @save="$emit('save-quick')"
            @cancel="$emit('close-add')"
            @skill-change="$emit('quick-skill-change')"
        />

        <QuestionTable
            :questions="group.questions"
            :can-review="canReview"
            :status-labels="statusLabels"
            :status-colors="statusColors"
            :skill-name-fn="skillNameFn"
            :bank-name-fn="bankNameFn"
            :selected-ids="selectedIds"
            @toggle="$emit('question-toggle', $event)"
            @review="(...args) => $emit('question-review', ...args)"
            @delete="(...args) => $emit('question-delete', ...args)"
        />
    </div>
</template>
