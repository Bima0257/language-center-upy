<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FakultasProgramStudiTable from './Components/FakultasProgramStudiTable.vue';

const props = defineProps({
    fakultas: { type: Array, default: () => [] },
});

function detailUrl(fakultasId) {
    return route('admin.data-induk.program-studi.fakultas.daftar', fakultasId);
}
</script>

<template>
    <Head title="Data Induk - Program Studi" />
    <TataLetakDasbor title="Data Induk Program Studi" subtitle="Pilih Fakultas untuk mengelola program studi">
        <FakultasProgramStudiTable :fakultas="props.fakultas" :detail-url="detailUrl" />
    </TataLetakDasbor>
</template>
