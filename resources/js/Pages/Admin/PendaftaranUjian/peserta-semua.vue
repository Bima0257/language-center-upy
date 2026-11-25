<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import RingkasanPesertaUjianTable from '@/Pages/Admin/PendaftaranUjian/Components/RingkasanPesertaUjianTable.vue';
import { statusPendaftaranOptions } from '@/constants/statusPendaftaran';

const props = defineProps({
    ringkasanUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
    programTes: { type: Array, default: () => [] },
});

const programTesOptions = computed(() => props.programTes.map(programTes => ({
    value: programTes.value,
    label: programTes.label,
})));

const tableFilters = computed(() => [
    { key: 'status', label: 'Approval Pendaftaran', options: statusPendaftaranOptions },
    { key: 'program_tes_id', label: 'Program Tes', options: programTesOptions.value },
]);

function detailUrl(ujianId) {
    return route('admin.pendaftaran-ujian.peserta-semua', { ujian_id: ujianId });
}
</script>

<template>
    <Head title="Peserta Ujian" />
    <TataLetakDasbor
        title="Peserta Ujian"
        subtitle="Ringkasan peserta pendaftaran per ujian"
        :back-href="route('admin.pendaftaran-ujian.daftar')"
        back-label="Pendaftaran Ujian"
    >
        <RingkasanPesertaUjianTable
            :ringkasan-ujian="ringkasanUjian"
            :filters="filters"
            :table-filters="tableFilters"
            table-key="admin-pendaftaran-ujian-peserta-semua-ringkasan"
            :detail-url="detailUrl"
        />
    </TataLetakDasbor>
</template>
