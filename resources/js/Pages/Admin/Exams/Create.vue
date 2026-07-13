<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';

const form = useForm({
    title: '',
    description: '',
    mode: 'tryout',
    duration_minutes: 160,
});

function submit() {
    form.post(route('admin.exams.store'));
}
</script>

<template>
    <Head title="Buat Ujian Baru" />
    <DashboardLayout title="Buat Ujian Baru">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl p-8 shadow-soft border border-outline-variant/30">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Judul Ujian</label>
                        <input type="text" v-model="form.title" required
                               placeholder="TOEFL iBT Try Out 2"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
                        <p v-if="form.errors.title" class="text-error-red text-xs mt-1">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Deskripsi</label>
                        <textarea v-model="form.description" rows="3"
                                  placeholder="Deskripsi ujian..."
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
                    <button type="submit" :disabled="form.processing"
                            class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Menyimpan...' : 'Buat Ujian' }}
                    </button>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
