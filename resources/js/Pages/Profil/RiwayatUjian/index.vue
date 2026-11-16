<script setup>
import { Head } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import AppDataTable from '@/Components/Ui/AppDataTable.vue'

defineProps({
    sesiUjian: { type: Array, default: () => [] },
})

const columns = [
    {
        accessorKey: 'ujian',
        header: 'Ujian',
        meta: { cellClass: 'font-medium text-text-heading' },
        cell: info => info.row.original.slot_ujian?.periode_ujian?.ujian?.judul || '-',
    },
    {
        accessorKey: 'created_at',
        header: 'Tanggal',
        cell: info => (info.getValue() ? new Date(info.getValue()).toLocaleDateString('id-ID') : '-'),
    },
    {
        accessorKey: 'skor_total',
        header: 'Skor',
        cell: info => (info.getValue() !== null
            ? `${info.getValue()}/${info.row.original.skor_total_maksimum ?? '-'}`
            : '-'),
    },
    {
        accessorKey: 'ditandai',
        header: 'Status',
        cell: info => (info.getValue() ? 'Perlu Tinjauan' : 'Normal'),
    },
]
</script>

<template>
    <Head title="Riwayat Tryout" />
    <TataLetakDasbor title="Riwayat Tryout" subtitle="Lihat hasil dan riwayat tryout Anda">
        <AppDataTable
            table-key="profil-riwayat-ujian"
            :data="sesiUjian"
            :columns="columns"
            searchable
        />
    </TataLetakDasbor>
</template>
