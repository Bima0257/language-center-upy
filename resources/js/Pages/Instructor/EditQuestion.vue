<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconInfoCircle } from '@tabler/icons-vue';

const props = defineProps({
    question: { type: Object, required: true },
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
    passages: { type: Array, default: () => [] },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const form = useForm({
    question_bank_id: props.question.question_bank_id || '',
    skill_id: props.question.skill_id || '',
    passage_id: props.question.passage_id || null,
    question_text: props.question.question_text,
    option_a: props.question.option_a || '',
    option_b: props.question.option_b || '',
    option_c: props.question.option_c || '',
    option_d: props.question.option_d || '',
    correct_answer: props.question.correct_answer || '',
});

function skillName(id) {
    return props.skills.find(s => s.id === id)?.name || '';
}

function bankName(id) {
    return props.questionBanks.find(b => b.id === id)?.name || '';
}

function submit() { form.put(route('content-library.update', props.question.id)); }
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
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Bank Soal <span class="text-error-red">*</span></label>
                            <select v-model="form.question_bank_id" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih Bank Soal</option><option v-for="b in questionBanks" :key="b.id" :value="b.id">{{ b.name }}</option></select></div>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Skill <span class="text-error-red">*</span></label>
                            <select v-model="form.skill_id" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih Skill</option><option v-for="s in skills" :key="s.id" :value="s.id">{{ s.name }}</option></select></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Materi Soal <span class="text-text-muted">(opsional)</span></label>
                            <select v-model="form.passage_id" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"><option :value="null">Tanpa Materi Soal</option><option v-for="p in passages" :key="p.id" :value="p.id">{{ p.title }}</option></select></div>
                    </div>
                    <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                        <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                        Perubahan akan direview ulang oleh admin sebelum bisa dipakai.
                    </p>

                    <hr class="border-outline-variant/50" />

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
                        <label class="text-label-md font-medium text-primary block mb-1.5">Kunci Jawaban <span class="text-error-red">*</span></label>
                        <select v-model="form.correct_answer" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                            <option value="" disabled>Pilih jawaban benar (A/B/C/D)</option>
                            <option v-for="key in optionKeys" :key="key" :value="key">{{ key }}</option>
                        </select>
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <button type="submit" :disabled="form.processing" class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95 disabled:opacity-50">{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</button>
                        <Link :href="route('content-library.index')" class="px-8 py-3.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Batal</Link>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
