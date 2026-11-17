<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import PesertaPendaftaranTable from '@/Pages/Admin/PendaftaranUjian/Components/PesertaPendaftaranTable.vue';
import { statusPendaftaranOptions } from '@/constants/statusPendaftaran';

defineProps({
    periodePendaftaranUjian: { type: Object, required: true },
    pendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
});

const tableFilters = [
    { key: 'status', label: 'Approval Pendaftaran', options: statusPendaftaranOptions },
];
</script>

<template>
    <Head title="Peserta Pendaftaran Ujian" />
    <TataLetakDasbor
        title="Peserta Pendaftaran Ujian"
        :subtitle="periodePendaftaranUjian.nama"
        :back-href="route('admin.pendaftaran-ujian.detail', periodePendaftaranUjian.id)"
        back-label="Detail Periode"
    >
        <PesertaPendaftaranTable
            :pendaftaran-ujian="pendaftaranUjian"
            :filters="filters"
            :table-filters="tableFilters"
            :table-key="`admin-pendaftaran-ujian-peserta-${periodePendaftaranUjian.id}`"
            title="Daftar Peserta"
        />
    </TataLetakDasbor>
</template>
