<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
});

const emit = defineEmits(['saved']);

const optionKeys = ['A', 'B', 'C', 'D'];

const form = useForm({
    question_bank_id: '',
    skill_id: '',
    question_text: '',
    option_a: '',
    option_b: '',
    option_c: '',
    option_d: '',
    correct_answer: '',
    order: 1,
});

function submit() {
    form.post(route('admin.exams.questions.store'), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); emit('saved'); },
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4 p-4 bg-surface-container-low rounded-2xl">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-label-md font-medium text-primary block mb-1">Bank Soal</label>
                <select v-model="form.question_bank_id" required
                        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                    <option value="" disabled>Pilih Bank Soal</option>
                    <option v-for="b in questionBanks" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
            </div>
            <div>
                <label class="text-label-md font-medium text-primary block mb-1">Skill <span class="text-error-red">*</span></label>
                <select v-model="form.skill_id" required
                        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                    <option value="" disabled>Pilih Skill</option>
                    <option v-for="s in skills" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>
        </div>
        <div>
            <label class="text-label-md font-medium text-primary block mb-1">Teks Soal</label>
            <BaseTextarea v-model="form.question_text" rows="2" required />
        </div>

        <div>
            <p class="text-label-md font-medium text-primary mb-2">Pilihan Jawaban</p>
            <div class="space-y-2">
                <div v-for="key in optionKeys" :key="key" class="flex items-center gap-2">
                    <span class="w-7 h-7 shrink-0 flex items-center justify-center rounded-full bg-surface-white border border-outline-variant font-semibold text-primary text-sm">{{ key }}</span>
                    <input type="text" v-model="form['option_' + key.toLowerCase()]" required :placeholder="'Teks pilihan ' + key"
                           class="flex-1 px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                </div>
            </div>
        </div>

        <div>
            <label class="text-label-md font-medium text-primary block mb-1">Kunci Jawaban</label>
            <select v-model="form.correct_answer" required
                    class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                <option value="" disabled>Pilih jawaban benar (A/B/C/D)</option>
                <option v-for="key in optionKeys" :key="key" :value="key">{{ key }}</option>
            </select>
        </div>

        <BaseButton type="submit" :disabled="form.processing">
            {{ form.processing ? 'Menyimpan...' : 'Tambah Soal' }}
        </BaseButton>
    </form>
</template>
