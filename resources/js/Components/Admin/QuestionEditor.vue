<script setup>
import { useForm } from '@inertiajs/vue3';
import OptionsEditor from '@/Components/Shared/OptionsEditor.vue';
import { computed } from 'vue';

const props = defineProps({
    groupId: { type: Number, required: true },
});

const emit = defineEmits(['saved']);

const form = useForm({
    type: 'multiple_choice',
    question_text: '',
    options: '',
    correct_answer: '',
    points: 1,
    order: 1,
});

const typeLabels = {
    multiple_choice: 'Pilihan Ganda',
    multi_select: 'Pilih >1 Jawaban',
    order: 'Urutkan',
    matching: 'Menjodohkan',
    fill_blank: 'Isian Singkat',
    essay: 'Essay',
    speaking: 'Speaking',
    true_false: 'True / False / Not Given',
    dictation: 'Dikte',
    error_id: 'Identifikasi Error',
};

const hasOptions = computed(() => ['multiple_choice', 'multi_select', 'order', 'matching', 'error_id'].includes(form.type));
const hasCorrectAnswer = computed(() => !['essay', 'speaking', 'dictation'].includes(form.type));
const correctLabel = computed(() => {
    if (form.type === 'true_false') return 'Jawaban Benar (True/False/Not Given)';
    if (form.type === 'fill_blank') return 'Jawaban Benar (pisah koma jika banyak alternatif)';
    if (form.type === 'error_id') return 'Bagian yang Salah (A/B/C/D)';
    if (form.type === 'order') return 'Urutan Benar (contoh: [1,3,2,4])';
    if (form.type === 'matching') return 'Pasangan Benar (JSON: [{"left":"A","right":"2"}])';
    if (form.type === 'multi_select') return 'Jawaban Benar (pisah koma: A,B)';
    return 'Kunci Jawaban';
});

function submit() {
    form.post(route('admin.exams.questions.store', props.groupId), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); emit('saved'); },
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4 p-4 bg-surface-container-low rounded-2xl">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-label-md font-medium text-primary block mb-1">Tipe Soal</label>
                <select v-model="form.type"
                        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                    <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div>
                <label class="text-label-md font-medium text-primary block mb-1">Poin</label>
                <input type="number" v-model="form.points" min="1"
                       class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
            </div>
        </div>
        <div>
            <label class="text-label-md font-medium text-primary block mb-1">Teks Soal</label>
            <textarea v-model="form.question_text" rows="2" required
                      class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"
                      :placeholder="form.type === 'dictation' ? 'Teks yang harus ditranskripsi peserta' : form.type === 'error_id' ? 'Kalimat lengkap dengan error, tulis bagian yang salah di Opsi' : ''"></textarea>
        </div>

        <OptionsEditor v-if="hasOptions" v-model="form.options" :type="form.type" />

        <div v-if="hasCorrectAnswer">
            <label class="text-label-md font-medium text-primary block mb-1">{{ correctLabel }}</label>
            <template v-if="form.type === 'true_false'">
                <select v-model="form.correct_answer"
                        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                    <option value="" disabled>Pilih jawaban</option>
                    <option value="A">True</option>
                    <option value="B">False</option>
                    <option value="C">Not Given</option>
                </select>
            </template>
            <template v-else-if="form.type === 'multiple_choice' && form.options">
                <select v-model="form.correct_answer"
                        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                    <option value="" disabled>Pilih jawaban</option>
                    <option v-for="o in (() => { try { const p=JSON.parse(form.options); return p; } catch{ return []; } })()" :key="o.key" :value="o.key">
                        {{ o.key }}. {{ o.text?.substring(0, 30) }}
                    </option>
                </select>
            </template>
            <template v-else>
                <input type="text" v-model="form.correct_answer" :placeholder="correctLabel"
                       class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
            </template>
        </div>

        <button type="submit" :disabled="form.processing"
                class="bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
            {{ form.processing ? 'Menyimpan...' : 'Tambah Soal' }}
        </button>
    </form>
</template>
