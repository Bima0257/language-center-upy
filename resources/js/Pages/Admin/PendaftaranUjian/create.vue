<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FormPeriodePendaftaran from '@/Pages/Admin/PendaftaranUjian/Components/FormPeriodePendaftaran.vue';

defineProps({
    periodeUjian: { type: Array, default: () => [] },
});

const form = useForm({
    periode_ujian_id: '',
    nama: '',
    dibuka_pada: '',
    ditutup_pada: '',
    aktif: true,
    catatan: '',
});

function submit() {
    form.post(route('admin.pendaftaran-ujian.simpan'));
}
</script>

<template>
    <Head title="Buat Periode Pendaftaran" />
    <TataLetakDasbor title="Buat Periode Pendaftaran" subtitle="Atur window pendaftaran dan informasi peserta">
        <div class="mx-auto max-w-2xl">
            <BaseCard padding="p-8">
                <FormPeriodePendaftaran :form="form" :periode-ujian="periodeUjian" creating @submit="submit" />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
