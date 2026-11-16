<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramTesAturanPenilaianTable from './Components/ProgramTesAturanPenilaianTable.vue';

defineProps({
    programTes: { type: Array, default: () => [] },
});

function detailUrl(programTesId) {
    return route('admin.data-induk.aturan-penilaian.program-tes.daftar', programTesId);
}
</script>

<template>
    <Head title="Data Induk - Aturan Penilaian" />
    <TataLetakDasbor
        title="Aturan Penilaian"
        subtitle="Kelola tabel konversi skor per program tes dan section"
    >
        <ProgramTesAturanPenilaianTable
            :program-tes="programTes"
            :detail-url="detailUrl"
        />
    </TataLetakDasbor>
</template>
