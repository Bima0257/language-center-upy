<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FormPeriodeUjian from '@/Pages/Admin/PeriodeUjian/Components/FormPeriodeUjian.vue';

const props = defineProps({
    periodeUjian: { type: Object, required: true },
});

const form = useForm({
    judul: props.periodeUjian.judul,
    tanggal_mulai: props.periodeUjian.tanggal_mulai?.substring(0, 10) || '',
    tanggal_selesai: props.periodeUjian.tanggal_selesai?.substring(0, 10) || '',
    aktif: props.periodeUjian.aktif,
});

function submit() {
    form.put(route('admin.periode-ujian.perbarui', props.periodeUjian.id));
}
</script>

<template>
    <Head title="Ubah Periode Ujian" />
    <TataLetakDasbor title="Ubah Periode Ujian" subtitle="Perbarui periode dan slotUjian ujian">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <p class="text-text-body text-body-md mb-6">Ujian: <strong>{{ periodeUjian.ujian?.judul }}</strong></p>
                <FormPeriodeUjian :form="form" @submit="submit" />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
