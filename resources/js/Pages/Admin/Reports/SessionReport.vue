<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconDownload } from '@tabler/icons-vue'

defineProps({
    sessions: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
})

const columns = [
    { key: 'name', label: 'Peserta', sortable: true, className: 'font-medium text-primary',
      render: (val, row) => row.user?.name || '-' },
    { key: 'exam', label: 'Ujian',
      render: (val, row) => row.schedule?.exam?.title || '-' },
    { key: 'status', label: 'Status', sortable: true, badge: true,
      render: (val) => {
          const m = { submitted: 'Selesai', terminated: 'Dihentikan', in_progress: 'Berlangsung', reviewed: 'Direview', pending: 'Menunggu' }
          return m[val] || val
      }},
    { key: 'score_total', label: 'Skor', sortable: true,
      render: (val) => val !== null ? `${val}/120` : '-' },
    { key: 'violation_strikes', label: 'Pelanggaran', sortable: true,
      render: (val) => val || 0 },
    { key: 'created_at', label: 'Tanggal', sortable: true,
      render: (val) => val ? new Date(val).toLocaleDateString('id-ID') : '-' },
]

function goToSession(session) {
    router.visit(route('proctor.session.show', session.id))
}
</script>

<template>
    <Head title="Laporan Sesi" />
    <DashboardLayout title="Laporan Sesi">
        <DataTable
            :data="sessions.data"
            :columns="columns"
            :links="sessions.links"
            :meta="sessions.meta"
            :row-link="goToSession"
            searchable
            search-placeholder="Cari peserta atau ujian..."
        />
    </DashboardLayout>
</template>
