<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';

const props = defineProps({
    exam: { type: Object, required: true },
});

const form = useForm({
    title: '',
    start_date: '',
    end_date: '',
});

function submit() {
    form.post(route('admin.schedules.store', props.exam.id));
}
</script>

<template>
    <Head title="Buat Periode Ujian" />
    <DashboardLayout title="Buat Periode Ujian">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <p class="text-text-body text-body-md mb-6">Ujian: <strong>{{ exam.title }}</strong></p>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Nama Periode</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Gelombang 1 — Agustus 2026"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Tanggal Mulai</label>
                            <input type="date" v-model="form.start_date" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Tanggal Selesai</label>
                            <input type="date" v-model="form.end_date" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <p class="text-label-md text-text-muted">
                        Periode menentukan rentang hari ujian. Sesi per hari diatur setelah periode dibuat (menu "Kelola Sesi").
                    </p>
                    <BaseButton type="submit" :disabled="form.processing" size="xl" class="w-full">
                        {{ form.processing ? 'Menyimpan...' : 'Buat Periode Ujian' }}
                    </BaseButton>
                </form>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
