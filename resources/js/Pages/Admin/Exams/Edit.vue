<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';

const props = defineProps({
    exam: { type: Object, required: true },
});

const form = useForm({
    title: props.exam.title,
    description: props.exam.description || '',
    mode: props.exam.mode,
    duration_minutes: props.exam.duration_minutes,
    is_active: props.exam.is_active,
});

function submit() {
    form.put(route('admin.exams.update', props.exam.id));
}
</script>

<template>
    <Head title="Edit Ujian" />
    <DashboardLayout title="Edit Ujian">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl p-8 shadow-soft border border-outline-variant/30">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Judul Ujian</label>
                        <input type="text" v-model="form.title" required
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Deskripsi</label>
                        <textarea v-model="form.description" rows="3"
                                  class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Tipe</label>
                            <select v-model="form.mode"
                                    class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                                <option value="tryout">Try Out</option>
                                <option value="official">Ujian Resmi</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Durasi (menit)</label>
                            <input type="number" v-model="form.duration_minutes" required min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" v-model="form.is_active"
                               class="rounded border-outline-variant text-primary focus:ring-secondary" />
                        <label for="is_active" class="text-text-body text-body-md">Aktif</label>
                    </div>
                    <button type="submit" :disabled="form.processing"
                            class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
