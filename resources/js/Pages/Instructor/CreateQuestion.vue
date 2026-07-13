<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import OptionsEditor from '@/Components/Shared/OptionsEditor.vue';

const props = defineProps({
    exams: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
});

const form = useForm({
    exam_id: '', section_type: 'reading', group_title: '',
    type: 'multiple_choice', question_text: '', options: '', correct_answer: '',
    points: 1, order: 1, passage_reference: '',
    difficulty: 'medium', status: 'draft', explanation: '', time_estimate: null,
    tags: [],
});

const typeLabels = {
    multiple_choice: 'Pilihan Ganda', multi_select: 'Pilih >1 Jawaban',
    order: 'Urutkan', matching: 'Menjodohkan', fill_blank: 'Isian Singkat',
    essay: 'Essay', speaking: 'Speaking', true_false: 'True / False / Not Given',
    dictation: 'Dikte', error_id: 'Identifikasi Error',
};
const sectionTypeLabels = { reading: 'Reading', listening: 'Listening', speaking: 'Speaking', writing: 'Writing', grammar: 'Grammar', vocabulary: 'Vocabulary' };

function submit() { form.post(route('content-library.store')); }

function toggleTag(id) {
    const idx = form.tags.indexOf(id);
    if (idx > -1) form.tags.splice(idx, 1);
    else form.tags.push(id);
}
</script>

<template>
    <Head title="Tambah Soal Baru" />
    <DashboardLayout title="Tambah Soal Baru">
        <Link :href="route('content-library.index')" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Content Library</Link>
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-3xl p-8 shadow-soft border border-outline-variant/30">
                <h2 class="text-headline-md font-bold text-primary mb-6">Tambah Soal Baru</h2>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Exam</label>
                            <select v-model="form.exam_id" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih Exam</option><option v-for="e in exams" :key="e.id" :value="e.id">{{ e.title }}</option></select>
                            <p v-if="form.errors.exam_id" class="text-error-red text-xs mt-1">{{ form.errors.exam_id }}</p></div>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Section</label>
                            <select v-model="form.section_type" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option v-for="(l,k) in sectionTypeLabels" :key="k" :value="k">{{ l }}</option></select></div>
                    </div>
                    <div><label class="text-label-md font-medium text-primary block mb-1.5">Nama Group <span class="text-text-muted">(opsional)</span></label>
                        <input type="text" v-model="form.group_title" placeholder="Passage 3 / Lecture 2 / ..." class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" /></div>

                    <hr class="border-outline-variant/50" />

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Tipe Soal</label>
                            <select v-model="form.type" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option v-for="(l,k) in typeLabels" :key="k" :value="k">{{ l }}</option></select></div>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Difficulty</label>
                            <select v-model="form.difficulty" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option value="easy">Mudah</option><option value="medium">Sedang</option><option value="hard">Sulit</option></select></div>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Status</label>
                            <select v-model="form.status" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option value="draft">Draft</option><option value="active">Aktif</option><option value="archived">Arsip</option></select></div>
                        <div><label class="text-label-md font-medium text-primary block mb-1.5">Poin</label>
                            <input type="number" v-model="form.points" min="1" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" /></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Waktu (detik) <span class="text-text-muted">opsional</span></label>
                                <input type="number" v-model="form.time_estimate" min="1" max="600" placeholder="30" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" /></div>
                        </div>
                        <div>
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Passage Reference <span class="text-text-muted">opsional</span></label>
                                <input type="text" v-model="form.passage_reference" placeholder="Paragraph 2" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" /></div>
                        </div>
                    </div>
                    <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal</label>
                        <textarea v-model="form.question_text" rows="3" required class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"></textarea></div>

                    <OptionsEditor v-model="form.options" :type="form.type" />

                    <div v-if="['multiple_choice','multi_select','true_false','fill_blank','order','matching','error_id'].includes(form.type)">
                        <label class="text-label-md font-medium text-primary block mb-1.5">
                            <template v-if="form.type === 'true_false'">Jawaban Benar</template>
                            <template v-else-if="form.type === 'fill_blank'">Jawaban Benar <span class="text-text-muted">(pisah koma)</span></template>
                            <template v-else-if="form.type === 'error_id'">Bagian yang Salah (A/B/C/D)</template>
                            <template v-else>Kunci Jawaban</template>
                        </label>
                        <template v-if="form.type === 'true_false'">
                            <select v-model="form.correct_answer" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih</option><option value="A">True</option><option value="B">False</option><option value="C">Not Given</option></select>
                        </template>
                        <template v-else-if="form.type === 'multiple_choice' && form.options">
                            <select v-model="form.correct_answer" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"><option value="" disabled>Pilih</option>
                                <option v-for="o in (() => { try { const p=JSON.parse(form.options); return p || []; } catch{ return []; } })()" :key="o.key" :value="o.key">{{ o.key }}. {{ o.text?.substring(0, 50) }}</option></select>
                        </template>
                        <template v-else><input type="text" v-model="form.correct_answer" placeholder="Jawaban benar" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" /></template>
                    </div>

                    <div><label class="text-label-md font-medium text-primary block mb-1.5">Penjelasan Jawaban <span class="text-text-muted">opsional — tampil setelah ujian</span></label>
                        <textarea v-model="form.explanation" rows="2" placeholder="Mengapa jawaban ini benar?" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"></textarea></div>

                    <div><label class="text-label-md font-medium text-primary block mb-1.5">Tags</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="tag in tags" :key="tag.id" type="button" @click="toggleTag(tag.id)"
                                    class="px-3 py-1.5 rounded-full text-label-md font-medium border transition-all"
                                    :class="form.tags.includes(tag.id) ? 'bg-primary-container text-white border-primary-container' : 'border-outline-variant text-text-body hover:border-secondary'">
                                {{ tag.name }}</button>
                        </div>
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <button type="submit" :disabled="form.processing" class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95 disabled:opacity-50">{{ form.processing ? 'Menyimpan...' : 'Simpan Soal' }}</button>
                        <Link :href="route('content-library.index')" class="px-8 py-3.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">Batal</Link>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
