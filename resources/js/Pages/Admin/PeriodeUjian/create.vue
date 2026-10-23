<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FormPeriodeUjian from '@/Pages/Admin/PeriodeUjian/components/FormPeriodeUjian.vue';

const props = defineProps({
    ujian: { type: Object, required: true },
});

const form = useForm({
    judul: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
});

function submit() {
    form.post(route('admin.periode-ujian.simpan', props.ujian.id));
}
</script>

<template>
    <Head title="Buat Periode Ujian" />
    <TataLetakDasbor title="Buat Periode Ujian" subtitle="Buat periode ujian baru beserta slotnya">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <p class="text-text-body text-body-md mb-6">Ujian: <strong>{{ ujian.judul }}</strong></p>
                <FormPeriodeUjian :form="form" creating @submit="submit" />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
