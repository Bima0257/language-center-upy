<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import FileUpload from '@/Components/Shared/FileUpload.vue';
import OptionsInput from '@/Components/ContentLibrary/OptionsInput.vue';
import AnswerKeyPicker from '@/Components/ContentLibrary/AnswerKeyPicker.vue';
import { useUploadProgress } from '@/Composables/useUploadProgress';
import { IconInfoCircle } from '@tabler/icons-vue';
import { computed } from 'vue';

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
}

const storedAudioUrl = computed(() => {
    const path = props.question.audio_url || props.question.passage?.audio_url;
    return path ? '/storage/' + path : null;
});

const storedImageUrl = computed(() => {
    const path = props.question.image_url || props.question.passage?.image_url;
    return path ? '/storage/' + path : null;
});

const indexUrl = computed(() => route('content-library.index', {
    question_bank_id: props.question.question_bank_id,
    skill_id: props.question.skill_id,
}));

const { showUploadProgress, uploadLabel, mediaType } = useUploadProgress(form);

function submit() {
    form.put(route('content-library.update', props.question.id));
}
</script>

<template>
    <Head title="Edit Soal" />
    <DashboardLayout title="Edit Soal">
        <Link :href="indexUrl" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Content Library</Link>
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
                                <FileUpload
                                    v-model="form.audio_file"
                                    label="Ganti Audio (opsional, max 50MB)"
                                    placeholder="Klik untuk upload audio"
                                    accept=".mp3,.wav,.ogg,.m4a"
                                    media-type="audio"
                                    :preview-url="storedAudioUrl"
                                    :error="form.errors.audio_file"
                                />
                                <FileUpload
                                    v-model="form.image_file"
                                    label="Ganti Gambar (opsional)"
                                    placeholder="Klik untuk upload gambar"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    media-type="image"
                                    :preview-url="storedImageUrl"
                                    :error="form.errors.image_file"
                                />
                            </div>

                            <AnswerKeyPicker v-model="form.correct_answer" :option-keys="optionKeys" />
                        </div>
                    </template>

                    <!-- MODE READING -->
                    <template v-else>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                            <BaseTextarea v-model="form.question_text" rows="3" required /></div>

                        <OptionsInput :form="form" :option-keys="optionKeys" />

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
                        <BaseButton type="submit" :disabled="form.processing" size="xl" class="flex-1">{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</BaseButton>
                        <BaseButton :href="indexUrl" variant="secondary" size="lg">Batal</BaseButton>
                    </div>
                </form>
            </div>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" :media-type="mediaType" />
    </DashboardLayout>
</template>
