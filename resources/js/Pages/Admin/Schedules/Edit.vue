<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';

const props = defineProps({
    schedule: { type: Object, required: true },
});

const form = useForm({
    title: props.schedule.title,
    start_date: props.schedule.start_date?.substring(0, 10) || '',
    end_date: props.schedule.end_date?.substring(0, 10) || '',
    is_active: props.schedule.is_active,
});

function submit() {
    form.put(route('admin.schedules.update', props.schedule.id));
}
</script>

<template>
    <Head title="Edit Periode Ujian" />
    <DashboardLayout title="Edit Periode Ujian">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <p class="text-text-body text-body-md mb-6">Ujian: <strong>{{ schedule.exam?.title }}</strong></p>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Nama Periode</label>
                        <input type="text" v-model="form.title" required
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
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
                    </label>
                    <BaseButton type="submit" :disabled="form.processing" size="xl" class="w-full">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </BaseButton>
                </form>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
