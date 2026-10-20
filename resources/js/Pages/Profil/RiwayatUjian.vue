<script setup>
import { Head } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import DataTable from '@/Components/Shared/DataTable.vue'

defineProps({
    sesiUjian: { type: Array, default: () => [] },
})

const columns = [
    { key: 'ujian', label: 'Ujian', sortable: true, className: 'font-medium text-primary',
      render: (val, row) => row.slot_ujian?.periode_ujian?.ujian?.judul || '-' },
    { key: 'created_at', label: 'Tanggal', sortable: true,
      render: (val) => val ? new Date(val).toLocaleDateString('id-ID') : '-' },
    { key: 'skor_total', label: 'Skor', sortable: true,
      render: (val) => val !== null ? `${val}/120` : '-' },
    { key: 'ditandai', label: 'Status', sortable: true, badge: true,
       render: (val) => val ? 'Perlu Tinjauan' : 'Normal' },
]
</script>

<template>
    <Head title="Riwayat Tryout" />
    <TataLetakDasbor title="Riwayat Tryout" subtitle="Lihat hasil dan riwayat tryout Anda">
        <DataTable
            :data="sesiUjian"
            :columns="columns"
            searchable
        />
    </TataLetakDasbor>
</template>
