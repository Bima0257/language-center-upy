<script setup>
import { Head, router } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import AppDataTable from '@/Components/Ui/AppDataTable.vue'

defineProps({
    sesiUjian: { type: Array, default: () => [] },
})

const statusLabels = {
    submitted: 'Selesai',
    terminated: 'Dihentikan',
    in_progress: 'Berlangsung',
    pending: 'Menunggu',
}

const columns = [
    {
        accessorKey: 'pengguna',
        header: 'Peserta',
        meta: { cellClass: 'font-medium text-text-heading' },
        cell: info => info.row.original.pengguna?.name || '-',
    },
    {
        accessorKey: 'ujian',
        header: 'Ujian',
        cell: info => info.row.original.slot_ujian?.periode_ujian?.ujian?.judul || '-',
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: info => statusLabels[info.getValue()] || info.getValue(),
    },
    {
        accessorKey: 'skor_total',
        header: 'Skor',
        cell: info => (info.getValue() !== null
            ? `${info.getValue()}/${info.row.original.skor_total_maksimum ?? '-'}`
            : '-'),
    },
    {
        accessorKey: 'jumlah_pelanggaran',
        header: 'Pelanggaran',
        cell: info => info.getValue() || 0,
    },
    {
        accessorKey: 'created_at',
        header: 'Tanggal',
        cell: info => (info.getValue() ? new Date(info.getValue()).toLocaleDateString('id-ID') : '-'),
    },
]

function goToSession(sesiUjian) {
    router.visit(route('pengawas.sesi.detail', sesiUjian.id))
}
</script>

<template>
    <Head title="Laporan Sesi" />
    <TataLetakDasbor title="Laporan Sesi" subtitle="Tinjau hasil, skor, dan pelanggaran sesi ujian">
        <AppDataTable
            table-key="admin-laporan-sesi"
            :data="sesiUjian"
            :columns="columns"
            :row-click="goToSession"
            searchable
            search-placeholder="Cari peserta atau ujian..."
        />
    </TataLetakDasbor>
</template>
