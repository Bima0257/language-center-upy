<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FormPeriodePendaftaran from '@/Pages/Admin/PendaftaranUjian/Components/FormPeriodePendaftaran.vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, required: true },
});

function localDateTime(value) {
    return value ? value.substring(0, 16).replace(' ', 'T') : '';
}

const form = useForm({
    nama: props.periodePendaftaranUjian.nama || '',
    dibuka_pada: localDateTime(props.periodePendaftaranUjian.dibuka_pada),
    ditutup_pada: localDateTime(props.periodePendaftaranUjian.ditutup_pada),
    aktif: props.periodePendaftaranUjian.aktif,
    catatan: props.periodePendaftaranUjian.catatan || '',
});

function submit() {
    form.put(route('admin.pendaftaran-ujian.perbarui', props.periodePendaftaranUjian.id));
}
</script>

<template>
    <Head title="Ubah Periode Pendaftaran" />
    <TataLetakDasbor title="Ubah Periode Pendaftaran" subtitle="Perbarui window dan informasi pendaftaran">
        <div class="mx-auto max-w-2xl">
            <BaseCard padding="p-8">
                <div class="mb-6 rounded-2xl bg-surface-container-low px-4 py-3 text-body-md text-text-body">
                    Periode ujian: <strong>{{ periodePendaftaranUjian.periode_ujian?.judul || '-' }}</strong>
                </div>
                <FormPeriodePendaftaran :form="form" @submit="submit" />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
