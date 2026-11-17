<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import PesertaPendaftaranTable from '@/Pages/Admin/PendaftaranUjian/Components/PesertaPendaftaranTable.vue';
import { statusPendaftaranOptions } from '@/constants/statusPendaftaran';

const props = defineProps({
    pendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
    programTes: { type: Array, default: () => [] },
    ujian: { type: Array, default: () => [] },
});

const programTesOptions = computed(() => props.programTes.map(programTes => ({
    value: programTes.value,
    label: programTes.label,
})));

const ujianOptions = computed(() => {
    const programTesId = props.filters?.program_tes_id;
    const daftarUjian = programTesId
        ? props.ujian.filter(ujian => String(ujian.program_tes_id) === String(programTesId))
        : props.ujian;

    return daftarUjian.map(ujian => ({
        value: ujian.value,
        label: ujian.label,
    }));
});

const tableFilters = computed(() => [
    { key: 'status', label: 'Approval Pendaftaran', options: statusPendaftaranOptions },
    { key: 'program_tes_id', label: 'Program Tes', options: programTesOptions.value },
    { key: 'ujian_id', label: 'Ujian', options: ujianOptions.value },
]);
</script>

<template>
    <Head title="Semua Peserta Pendaftaran Ujian" />
    <TataLetakDasbor
        title="Semua Peserta Pendaftaran Ujian"
        subtitle="Pantau dan setujui pendaftaran peserta lintas periode dan slot"
        :back-href="route('admin.pendaftaran-ujian.daftar')"
        back-label="Pendaftaran Ujian"
    >
        <PesertaPendaftaranTable
            :pendaftaran-ujian="pendaftaranUjian"
            :filters="filters"
            :table-filters="tableFilters"
            table-key="admin-pendaftaran-ujian-peserta-semua"
            title="Daftar Semua Peserta"
            show-exam-context
        />
    </TataLetakDasbor>
</template>
