<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramTesSectionTable from './Components/ProgramTesSectionTable.vue';

const props = defineProps({
    programTes: { type: Array, default: () => [] },
});

function detailUrl(programTesId) {
    return route('admin.data-induk.section.program-tes.daftar', programTesId);
}
</script>

<template>
    <Head title="Data Induk - Section" />
    <TataLetakDasbor title="Data Induk Section" subtitle="Pilih program tes untuk mengelola section">
        <ProgramTesSectionTable :program-tes="props.programTes" :detail-url="detailUrl" />
    </TataLetakDasbor>
</template>
