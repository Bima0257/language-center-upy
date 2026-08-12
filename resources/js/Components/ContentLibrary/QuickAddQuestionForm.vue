<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import OptionsInput from '@/Components/ContentLibrary/OptionsInput.vue';
import { IconHeadphones, IconCheck } from '@tabler/icons-vue';

defineProps({
    form: { type: Object, required: true },
    passageTitle: { type: String, default: '' },
    questionBanks: { type: Array, default: () => [] },
    availableQuickSkills: { type: Array, default: () => [] },
    quickPartsFn: { type: Function, default: () => [] },
    quickIsAudio: { type: Boolean, default: false },
    optionKeys: { type: Array, default: () => [] },
});

defineEmits(['save', 'cancel', 'skill-change']);
</script>

<template>
    <div class="px-5 py-4 bg-pastel-blue/10 border-b border-outline-variant/30">
        <p class="text-label-md font-semibold text-primary mb-3">Tambah Soal ke "{{ passageTitle }}"</p>
        <form @submit.prevent="$emit('save')" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <DropDown
                        v-model="form.question_bank_id"
                        :options="questionBanks"
                        label="Bank Soal *"
                        placeholder="Pilih Bank"
                        option-label="name"
                        option-value="id"
                        size="sm"
                    />
                    <p v-if="form.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ form.errors.question_bank_id }}</p>
                </div>
                <div>
                    <DropDown
                        v-model="form.skill_id"
                        :options="availableQuickSkills"
                        label="Skill *"
                        placeholder="Pilih Skill"
                        option-label="name"
                        option-value="id"
                        size="sm"
                        @change="$emit('skill-change')"
                    />
                </div>
                <div>
                    <DropDown
                        v-model="form.skill_part_id"
                        :options="quickPartsFn()"
                        label="Part *"
                        placeholder="Pilih part"
                        option-label="name"
                        option-value="id"
                        size="sm"
                        :disabled="!form.skill_id"
                    />
                </div>
                <div>
                    <DropDown
                        v-model="form.material_type"
                        :options="[
                            { id: 'text', name: 'Teks (Reading)' },
                            { id: 'audio', name: 'Audio + Gambar' },
                        ]"
                        label="Tipe Materi *"
                        placeholder="Pilih tipe"
                        option-label="name"
                        option-value="id"
                        size="sm"
                    />
                </div>
                <div>
                    <DropDown
                        v-model="form.correct_answer"
                        :options="optionKeys.map(k => ({ id: k, name: k }))"
                        label="Kunci Jawaban *"
                        placeholder="Pilih kunci"
                        option-label="name"
                        option-value="id"
                        size="sm"
                    />
                </div>
            </div>
            <template v-if="quickIsAudio">
                <p class="flex items-center gap-1.5 text-label-md text-text-muted bg-pastel-purple/10 border border-pastel-purple/40 rounded-xl px-4 py-3">
                    <IconHeadphones :size="16" class="text-secondary shrink-0" />
                    Soal tipe audio memakai audio passage ini — tanpa teks soal dan pilihan jawaban.
                </p>
            </template>
            <template v-else>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1">Teks Soal <span class="text-error-red">*</span></label>
                    <BaseTextarea v-model="form.question_text" rows="2" required />
                    <p v-if="form.errors.question_text" class="text-error-red text-xs mt-1">{{ form.errors.question_text }}</p>
                </div>
                <OptionsInput :form="form" :option-keys="optionKeys" size="sm" />
            </template>
            <div class="flex items-center gap-2">
                <BaseButton type="submit" :disabled="form.processing" size="xs">
                    <IconCheck :size="16" /> {{ form.processing ? 'Menyimpan...' : 'Simpan Soal' }}
                </BaseButton>
                <BaseButton type="button" variant="secondary" size="xs" @click="$emit('cancel')">
                    Batal
                </BaseButton>
            </div>
        </form>
    </div>
</template>
