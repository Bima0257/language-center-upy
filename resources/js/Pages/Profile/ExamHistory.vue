<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DataTable from '@/Components/Shared/DataTable.vue'

defineProps({
    sessions: { type: Array, default: () => [] },
})

const columns = [
    { key: 'exam', label: 'Ujian', sortable: true, className: 'font-medium text-primary',
      render: (val, row) => row.schedule?.exam?.title || '-' },
    { key: 'created_at', label: 'Tanggal', sortable: true,
      render: (val) => val ? new Date(val).toLocaleDateString('id-ID') : '-' },
    { key: 'score_total', label: 'Skor', sortable: true,
      render: (val) => val !== null ? `${val}/120` : '-' },
    { key: 'is_flagged', label: 'Status', sortable: true, badge: true,
      render: (val) => val ? 'Perlu Review' : 'Normal' },
]
</script>

<template>
    <Head title="Riwayat Tryout" />
    <DashboardLayout title="Riwayat Tryout">
        <DataTable
            :data="sessions"
            :columns="columns"
        />
    </DashboardLayout>
</template>
