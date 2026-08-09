<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconInfoCircle, IconUpload } from '@tabler/icons-vue';
import { computed, ref } from 'vue';

const props = defineProps({
    question: { type: Object, required: true },
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    passages: { type: Array, default: () => [] },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const form = useForm({
    question_bank_id: props.question.question_bank_id || '',
    skill_id: props.question.skill_id || '',
    skill_part_id: props.question.skill_part_id || '',
    passage_id: props.question.passage_id || null,
    question_text: props.question.question_text || '',
    option_a: props.question.option_a || '',
    option_b: props.question.option_b || '',
    option_c: props.question.option_c || '',
    option_d: props.question.option_d || '',
    correct_answer: props.question.correct_answer || '',
    audio_file: null,
    image_file: null,
});

const audioFileName = ref(null);
const imageFileName = ref(null);
const audioPreviewUrl = ref(null);
const imagePreviewUrl = ref(null);

const selectedBank = computed(() =>
    props.questionBanks.find(b => String(b.id) === String(form.question_bank_id)) || null,
);

const availableSkills = computed(() => {
    if (!selectedBank.value) return props.skills;
    return props.skills.filter(s => String(s.exam_type_id) === String(selectedBank.value.exam_type_id));
});

const selectedSkill = computed(() =>
    props.skills.find(s => String(s.id) === String(form.skill_id)) || null,
);

const isListening = computed(() => selectedSkill.value?.code === 'listening');

const partsForSelectedSkill = computed(() =>
    props.parts.filter(p => String(p.skill_id) === String(form.skill_id)),
);

function skillName(id) {
    return props.skills.find(s => String(s.id) === String(id))?.name || '';
}

function onSkillChange() {
    form.skill_part_id = '';
    form.audio_file = null;
    form.image_file = null;
    audioFileName.value = null;
    imageFileName.value = null;
    audioPreviewUrl.value = null;
    imagePreviewUrl.value = null;
}

function onAudioSelect(e) {
    form.audio_file = e.target.files[0] || null;
    audioFileName.value = form.audio_file?.name || null;
    audioPreviewUrl.value = form.audio_file ? URL.createObjectURL(form.audio_file) : null;
    e.target.value = '';
}

function onImageSelect(e) {
    form.image_file = e.target.files[0] || null;
    imageFileName.value = form.image_file?.name || null;
    imagePreviewUrl.value = form.image_file ? URL.createObjectURL(form.image_file) : null;
    e.target.value = '';
}

const storedAudioUrl = computed(() => {
    const path = props.question.audio_url || props.question.passage?.audio_url;
    return path ? '/storage/' + path : null;
});

const storedImageUrl = computed(() => {
    const path = props.question.image_url || props.question.passage?.image_url;
    return path ? '/storage/' + path : null;
});

const showUploadProgress = computed(() => form.processing && (form.audio_file !== null || form.image_file !== null));
const uploadLabel = computed(() => form.audio_file !== null ? 'Mengunggah & mengompres audio...' : 'Mengunggah & mengompres gambar...');

function submit() {
    form.put(route('content-library.update', props.question.id));
}
</script>

<template>
    <Head title="Edit Soal" />
    <DashboardLayout title="Edit Soal">
        <Link :href="route('content-library.index')" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Content Library</Link>
        <div class="max-w-5xl mx-auto">
            <div class="bg-surface-white rounded-3xl p-8 shadow-soft border border-outline-variant/30">
                <div class="mb-6">
                    <h2 class="text-headline-md font-bold text-primary">Edit Soal</h2>
                    <p class="text-text-muted text-text-body text-body-md mt-1">Pilihan Ganda — {{ skillName(question.skill_id) }}</p>
                </div>
                <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <DropDown
                                v-model="form.question_bank_id"
                                :options="questionBanks"
                                label="Bank Soal *"
                                placeholder="Pilih Bank Soal"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                        <div>
                            <DropDown
                                v-model="form.skill_id"
                                :options="availableSkills"
                                label="Skill *"
                                placeholder="Pilih Skill"
                                option-label="name"
                                option-value="id"
                                @change="onSkillChange"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <DropDown
                                v-model="form.skill_part_id"
                                :options="partsForSelectedSkill"
                                label="Part *"
                                placeholder="Pilih part"
                                option-label="name"
                                option-value="id"
                                :disabled="!form.skill_id"
                            />
                        </div>
                        <div>
                            <DropDown
                                v-model="form.passage_id"
                                :options="passages"
                                label="Materi Soal (opsional)"
                                placeholder="Tanpa Materi Soal"
                                option-label="title"
                                option-value="id"
                                clearable
                            />
                        </div>
                    </div>
                    <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                        <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                        Perubahan akan direview ulang oleh admin sebelum bisa dipakai.
                    </p>

                    <hr class="border-outline-variant/50" />

                    <!-- MODE LISTENING -->
                    <template v-if="isListening">
                        <div class="bg-pastel-purple/10 border border-pastel-purple/40 rounded-2xl p-5 space-y-4">
                            <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                                <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                                Soal, materi, dan pilihan jawaban berada di audio. Peserta hanya memilih A/B/C/D.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 min-h-[120px] flex flex-col">
                                    <p class="text-label-md font-semibold text-primary mb-2">Preview Audio</p>
                                    <div class="flex-1 flex items-center">
                                        <audio v-if="audioPreviewUrl" :src="audioPreviewUrl" controls class="w-full h-10" />
                                        <audio v-else-if="storedAudioUrl" :src="storedAudioUrl" controls class="w-full h-10" />
                                        <p v-else class="text-label-md text-text-muted">Belum ada audio</p>
                                    </div>
                                </div>
                                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 min-h-[120px] flex flex-col">
                                    <p class="text-label-md font-semibold text-primary mb-2">Preview Gambar</p>
                                    <div class="flex-1 flex items-center justify-center">
                                        <img v-if="imagePreviewUrl" :src="imagePreviewUrl"
                                             class="max-h-28 rounded-lg border border-outline-variant/30 object-contain" />
                                        <img v-else-if="storedImageUrl" :src="storedImageUrl"
                                             class="max-h-28 rounded-lg border border-outline-variant/30 object-contain" />
                                        <p v-else class="text-label-md text-text-muted">Belum ada gambar (opsional)</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-label-md font-medium text-primary block mb-1.5">Ganti Audio <span class="text-text-muted">(opsional, max 50MB)</span></label>
                                    <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-2xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors">
                                        <IconUpload :size="18" class="text-text-muted" />
                                        <span class="text-label-md text-text-body">{{ audioFileName || 'Klik untuk upload audio' }}</span>
                                        <input type="file" accept=".mp3,.wav,.ogg,.m4a" class="hidden" @change="onAudioSelect" />
                                    </label>
                                    <p v-if="form.errors.audio_file" class="text-error-red text-xs mt-1">{{ form.errors.audio_file }}</p>
                                </div>
                                <div>
                                    <label class="text-label-md font-medium text-primary block mb-1.5">Ganti Gambar <span class="text-text-muted">(opsional)</span></label>
                                    <label class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-2xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors">
                                        <IconUpload :size="18" class="text-text-muted" />
                                        <span class="text-label-md text-text-body">{{ imageFileName || 'Klik untuk upload gambar' }}</span>
                                        <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onImageSelect" />
                                    </label>
                                    <p v-if="form.errors.image_file" class="text-error-red text-xs mt-1">{{ form.errors.image_file }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-label-md font-medium text-primary mb-2">Kunci Jawaban <span class="text-error-red">*</span></p>
                                <div class="flex flex-wrap gap-3">
                                    <span v-for="key in optionKeys" :key="key"
                                          class="flex items-center gap-2 px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest">
                                        <input type="checkbox" :checked="form.correct_answer === key" @change="form.correct_answer = key"
                                               class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                                        <span class="font-semibold text-primary">{{ key }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- MODE READING -->
                    <template v-else>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                            <textarea v-model="form.question_text" rows="3" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"></textarea></div>

                        <div>
                            <p class="text-label-md font-medium text-primary mb-2">Pilihan Jawaban <span class="text-error-red">*</span></p>
                            <div class="space-y-3">
                                <div v-for="key in optionKeys" :key="key" class="flex items-center gap-3">
                                    <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-surface-container-low border border-outline-variant font-semibold text-primary">{{ key }}</span>
                                    <input type="text" v-model="form['option_' + key.toLowerCase()]" required :placeholder="'Teks pilihan ' + key"
                                           class="flex-1 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <DropDown
                                v-model="form.correct_answer"
                                :options="optionKeys.map(k => ({ id: k, name: k }))"
                                label="Kunci Jawaban *"
                                placeholder="Pilih jawaban benar (A/B/C/D)"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                    </template>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <button type="submit" :disabled="form.processing" class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95 disabled:opacity-50">{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</button>
                        <Link :href="route('content-library.index')" class="px-8 py-3.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Batal</Link>
                    </div>
                </form>
            </div>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" />
    </DashboardLayout>
</template>
