<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import OptionsInput from '@/Components/ContentLibrary/OptionsInput.vue';
import AnswerKeyPicker from '@/Components/ContentLibrary/AnswerKeyPicker.vue';
import { IconInfoCircle } from '@tabler/icons-vue';
import { computed } from 'vue';
import { materialOfSkill } from '@/constants/skills';

const props = defineProps({
    question: { type: Object, required: true },
    questionBanks: { type: Array, default: () => [] },
    skillOptions: { type: Array, default: () => [] },
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
});

function skillCodeById(skillId) {
    return props.skillOptions.find(s => String(s.value) === String(skillId))?.code || '';
}

const isMaterialAudio = computed(() => materialOfSkill(skillCodeById(form.skill_id)) === 'audio');

const selectedPassage = computed(() =>
    props.passages.find((p) => String(p.id) === String(form.passage_id)) || null,
);

const partsForSelectedSkill = computed(() =>
    props.parts.filter((p) =>
        String(p.skill_id) === String(form.skill_id) &&
        String(p.question_bank_id) === String(form.question_bank_id),
    ),
);

function onSkillChange() {
    form.skill_part_id = '';
}

const indexUrl = computed(() => route('content-library.index', {
    question_bank_id: props.question.question_bank_id,
    skill_id: props.question.skill_id,
}));

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
                    <p class="text-text-muted text-text-body text-body-md mt-1">Pilihan Ganda — {{ skillCodeById(form.skill_id) || 'Unknown' }}</p>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
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
                            <p class="text-label-md font-medium text-primary mb-2">Skill *</p>
                            <div class="flex flex-wrap gap-3">
                                <label v-for="skill in skillOptions" :key="skill.value"
                                       class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                                       :class="form.skill_id === skill.value ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                                    <input type="radio" name="skill" :value="skill.value" v-model="form.skill_id"
                                           @change="onSkillChange"
                                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                                    <span class="font-semibold" :class="form.skill_id === skill.value ? 'text-secondary' : 'text-primary'">{{ skill.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-label-md font-medium text-primary mb-2">Part *</p>
                        <div v-if="partsForSelectedSkill.length" class="flex flex-wrap gap-3">
                            <label v-for="part in partsForSelectedSkill" :key="part.id"
                                   class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                                   :class="form.skill_part_id === part.id ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                                <input type="radio" name="part" :value="part.id" v-model="form.skill_part_id"
                                       class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                                <span class="font-semibold" :class="form.skill_part_id === part.id ? 'text-secondary' : 'text-primary'">{{ part.name }}</span>
                            </label>
                        </div>
                        <p v-else class="text-label-md text-text-muted">Belum ada part untuk skill ini.</p>
                    </div>
                    <div>
                        <DropDown
                            v-model="form.passage_id"
                            :options="passages"
                            label="Materi Soal"
                            :placeholder="isMaterialAudio ? 'Pilih passage audio (wajib)' : 'Tanpa Materi Soal'"
                            option-label="title"
                            option-value="id"
                            clearable
                        />
                    </div>
                    <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                        <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                        Perubahan akan direview ulang oleh admin sebelum bisa dipakai.
                    </p>

                    <hr class="border-outline-variant/50" />

                    <!-- MODE LISTENING: audio dari passage -->
                    <template v-if="isMaterialAudio">
                        <div class="bg-pastel-purple/10 border border-pastel-purple/40 rounded-2xl p-5 space-y-4">
                            <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                                <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                                Soal, materi, dan pilihan jawaban berada di audio passage. Peserta hanya memilih A/B/C/D.
                            </p>
                            <p v-if="!selectedPassage?.audio_url" class="text-label-md text-error-red">
                                Soal listening wajib memakai passage yang memiliki audio.
                            </p>
                            <p v-else class="text-label-md text-text-body">
                                Audio: {{ selectedPassage.title }}
                            </p>
                            <AnswerKeyPicker v-model="form.correct_answer" :option-keys="optionKeys" />
                        </div>
                    </template>

                    <!-- MODE TEKS -->
                    <template v-else>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                            <BaseTextarea v-model="form.question_text" rows="3" required /></div>

                        <OptionsInput :form="form" :option-keys="optionKeys" />

                        <AnswerKeyPicker v-model="form.correct_answer" :option-keys="optionKeys" />
                    </template>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="form.processing" size="xl" class="flex-1">{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</BaseButton>
                        <BaseButton :href="indexUrl" variant="secondary" size="lg">Batal</BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
