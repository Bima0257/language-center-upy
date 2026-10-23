<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FormUjian from '@/Pages/Admin/Ujian/Components/FormUjian.vue';

const props = defineProps({
    ujian: { type: Object, required: true },
    jenisTes: { type: Array, default: () => [] },
});

const form = useForm({
    jenis_tes_id: props.ujian.jenis_tes_id,
    judul: props.ujian.judul,
    deskripsi: props.ujian.deskripsi || '',
    mode: props.ujian.mode,
    durasi_menit: props.ujian.durasi_menit,
    aktif: props.ujian.aktif,
});

function submit() {
    form.put(route('admin.ujian.perbarui', props.ujian.id));
}
</script>

<template>
    <Head title="Ubah Ujian" />
    <TataLetakDasbor title="Ubah Ujian" subtitle="Perbarui pengaturan dan komposisi ujian">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <FormUjian :form="form" :jenis-tes="jenisTes" @submit="submit" />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
