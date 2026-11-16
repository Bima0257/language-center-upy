<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramTesInterpretasiSkorTable from './Components/ProgramTesInterpretasiSkorTable.vue';

const props = defineProps({
    programTes: { type: Array, default: () => [] },
});

function detailUrl(programTesId) {
    return route('admin.data-induk.interpretasi-skor.program-tes.daftar', programTesId);
}
</script>

<template>
    <Head title="Data Induk - Interpretasi Skor" />
    <TataLetakDasbor title="Data Induk Interpretasi Skor" subtitle="Pilih program tes untuk mengelola interpretasi skor">
        <ProgramTesInterpretasiSkorTable :program-tes="props.programTes" :detail-url="detailUrl" />
    </TataLetakDasbor>
</template>
