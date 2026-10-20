<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import JenisTesInterpretasiSkorTable from './InterpretasiSkor/Components/JenisTesInterpretasiSkorTable.vue';

const props = defineProps({
    jenisTes: { type: Array, default: () => [] },
});

function detailUrl(jenisTesId) {
    return route('admin.data-induk.interpretasi-skor.jenis-tes.daftar', jenisTesId);
}
</script>

<template>
    <Head title="Data Induk - Interpretasi Skor" />
    <TataLetakDasbor title="Data Induk Interpretasi Skor" subtitle="Pilih jenis tes untuk mengelola interpretasi skor">
        <JenisTesInterpretasiSkorTable :jenis-tes="props.jenisTes" :detail-url="detailUrl" />
    </TataLetakDasbor>
</template>
