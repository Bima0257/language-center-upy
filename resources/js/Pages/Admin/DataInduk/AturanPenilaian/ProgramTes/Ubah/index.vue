<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import AturanPenilaianForm from '../../Components/AturanPenilaianForm.vue';

const props = defineProps({
    programTes: { type: Object, required: true },
    skema: { type: Object, required: true },
    section: { type: Object, required: true },
    aturan: { type: Object, default: null },
    memilikiUjianAktif: { type: Boolean, default: false },
});

const detailUrl = route(
    'admin.data-induk.aturan-penilaian.program-tes.daftar',
    props.programTes.id,
);

const form = useForm({
    tabel_konversi: { ...(props.aturan?.tabel_konversi || {}) },
    skor_mentah_maksimum: props.aturan?.skor_mentah_maksimum || 50,
    skor_skala_minimum: props.aturan?.skor_skala_minimum ?? 100,
    skor_skala_maksimum: props.aturan?.skor_skala_maksimum || 200,
});

function submit() {
    if (props.memilikiUjianAktif) return;

    form.put(route(
        'admin.data-induk.aturan-penilaian.skema.section.perbarui',
        [props.skema.id, props.section.id],
    ), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Ubah Aturan Penilaian - ${props.section.nama_section}`" />
    <TataLetakDasbor
        title="Ubah Aturan Penilaian"
        :subtitle="`${props.section.nama_section} (${props.section.kode_section}) — ${props.programTes.nama}`"
        :back-href="detailUrl"
        back-label="Kembali ke Aturan Penilaian"
    >
        <BaseCard padding="p-8">
            <AturanPenilaianForm
                :form="form"
                :cancel-href="detailUrl"
                :default-editing="!props.aturan"
                :locked="props.memilikiUjianAktif"
                @submit="submit"
            />
        </BaseCard>
    </TataLetakDasbor>
</template>
