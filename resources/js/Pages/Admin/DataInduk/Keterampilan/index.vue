<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import JenisTesKeterampilanTable from './components/JenisTesKeterampilanTable.vue';

const props = defineProps({
    jenisTes: { type: Array, default: () => [] },
});

function detailUrl(jenisTesId) {
    return route('admin.data-induk.keterampilan.jenis-tes.daftar', jenisTesId);
}
</script>

<template>
    <Head title="Data Induk - Keterampilan" />
    <TataLetakDasbor title="Data Induk Keterampilan" subtitle="Pilih jenis tes untuk mengelola keterampilan">
        <JenisTesKeterampilanTable :jenis-tes="props.jenisTes" :detail-url="detailUrl" />
    </TataLetakDasbor>
</template>
