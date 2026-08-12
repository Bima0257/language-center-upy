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
    quickIsListening: { type: Boolean, default: false },
    optionKeys: { type: Array, default: () => [] },
});

defineEmits(['save', 'cancel', 'skill-change']);
</script>

<template>
    <div class="px-5 py-4 bg-pastel-blue/10 border-b border-outline-variant/30">
        <p class="text-label-md font-semibold text-primary mb-3">Tambah Soal ke "{{ passageTitle }}"</p>
        <form @submit.prevent="$emit('save')" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
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
            <template v-if="quickIsListening">
                <p class="flex items-center gap-1.5 text-label-md text-text-muted bg-pastel-purple/10 border border-pastel-purple/40 rounded-xl px-4 py-3">
                    <IconHeadphones :size="16" class="text-secondary shrink-0" />
                    Soal listening memakai audio passage ini — tanpa teks soal dan pilihan jawaban.
                </p>
            </template>
            <template v-else>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1">Teks Soal <span class="text-error-red">*</span></label>
                    <textarea v-model="form.question_text" rows="2" required
                              class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary"></textarea>
                    <p v-if="form.errors.question_text" class="text-error-red text-xs mt-1">{{ form.errors.question_text }}</p>
                </div>
                <OptionsInput :form="form" :option-keys="optionKeys" size="sm" />
            </template>
            <div class="flex items-center gap-2">
                <button type="submit" :disabled="form.processing"
                        class="flex items-center gap-1.5 bg-primary-container text-white px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                    <IconCheck :size="16" /> {{ form.processing ? 'Menyimpan...' : 'Simpan Soal' }}
                </button>
                <button type="button" @click="$emit('cancel')"
                        class="flex items-center gap-1.5 px-5 py-2.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                    Batal
                </button>
            </div>
        </form>
    </div>
</template>
