<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import PesertaPendaftaranTable from '@/Pages/Admin/PendaftaranUjian/Components/PesertaPendaftaranTable.vue';
import { statusPendaftaranOptions } from '@/constants/statusPendaftaran';

const props = defineProps({
    ujianTerpilih: { type: Object, required: true },
    pendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
});

const tableFilters = [
    { key: 'status', label: 'Approval Pendaftaran', options: statusPendaftaranOptions },
];

const modeLabel = computed(() => (props.ujianTerpilih.mode === 'tryout' ? 'Try Out' : 'Ujian Resmi'));

const subtitle = computed(() => `${props.ujianTerpilih.program_tes?.nama || '-'} • ${modeLabel.value}`);
</script>

<template>
    <Head :title="`Peserta - ${ujianTerpilih.judul}`" />
    <TataLetakDasbor
        :title="`Peserta - ${ujianTerpilih.judul}`"
        :subtitle="subtitle"
        :back-href="route('admin.pendaftaran-ujian.peserta-semua')"
        back-label="Ringkasan Ujian"
    >
        <PesertaPendaftaranTable
            :pendaftaran-ujian="pendaftaranUjian"
            :filters="filters"
            :table-filters="tableFilters"
            :table-key="`admin-pendaftaran-ujian-peserta-ujian-${ujianTerpilih.id}`"
            title="Daftar Peserta"
        />
    </TataLetakDasbor>
</template>
