<script setup>
import { Head, router } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import DataTable from '@/Components/Shared/DataTable.vue'

defineProps({
    sesiUjian: { type: Array, default: () => [] },
})

const columns = [
    { key: 'pengguna', label: 'Peserta', sortable: true, className: 'font-medium text-primary',
      render: (val, row) => row.pengguna?.name || '-' },
    { key: 'ujian', label: 'Ujian',
      render: (val, row) => row.slot_ujian?.periode_ujian?.ujian?.judul || '-' },
    { key: 'status', label: 'Status', sortable: true, badge: true,
      render: (val) => {
          const m = { submitted: 'Selesai', terminated: 'Dihentikan', in_progress: 'Berlangsung', pending: 'Menunggu' }
          return m[val] || val
      }},
    { key: 'skor_total', label: 'Skor', sortable: true,
      render: (val) => val !== null ? `${val}/120` : '-' },
    { key: 'jumlah_pelanggaran', label: 'Pelanggaran', sortable: true,
      render: (val) => val || 0 },
    { key: 'created_at', label: 'Tanggal', sortable: true,
      render: (val) => val ? new Date(val).toLocaleDateString('id-ID') : '-' },
]

function goToSession(sesiUjian) {
    router.visit(route('pengawas.sesi.detail', sesiUjian.id))
}
</script>

<template>
    <Head title="Laporan Sesi" />
    <TataLetakDasbor title="Laporan Sesi" subtitle="Tinjau hasil, skor, dan pelanggaran sesi ujian">
        <DataTable
            :data="sesiUjian"
            :columns="columns"
            :row-link="goToSession"
            searchable
            search-placeholder="Cari peserta atau ujian..."
        />
    </TataLetakDasbor>
</template>
