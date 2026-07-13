<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';

const props = defineProps({
    exam: { type: Object, required: true },
});

const form = useForm({
    title: '',
    scheduled_start: '',
    scheduled_end: '',
    late_tolerance_minutes: 15,
    max_participants: 30,
});

function submit() {
    form.post(route('admin.schedules.store', props.exam.id));
}
</script>

<template>
    <Head title="Buat Jadwal" />
    <DashboardLayout title="Buat Jadwal">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl p-8 shadow-soft border border-outline-variant/30">
                <p class="text-text-body text-body-md mb-6">Ujian: <strong>{{ exam.title }}</strong></p>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Nama Sesi</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Sesi 1 — 10 Juli 2026"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Mulai</label>
                            <input type="datetime-local" v-model="form.scheduled_start" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Selesai</label>
                            <input type="datetime-local" v-model="form.scheduled_end" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Toleransi (menit)</label>
                            <input type="number" v-model="form.late_tolerance_minutes" min="0"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Kuota Peserta</label>
                            <input type="number" v-model="form.max_participants" min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <button type="submit" :disabled="form.processing"
                            class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Menyimpan...' : 'Buat Jadwal' }}
                    </button>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
