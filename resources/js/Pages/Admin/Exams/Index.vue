<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconPlus, IconEye } from '@tabler/icons-vue'

defineProps({
    exams: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
})

const columns = [
    { key: 'title', label: 'Judul Ujian', sortable: true, className: 'font-medium text-primary' },
    { key: 'mode', label: 'Mode', sortable: true, badge: true,
      render: (val) => val === 'tryout' ? 'Try Out' : 'Ujian Resmi' },
    { key: 'duration_minutes', label: 'Durasi',
      render: (val) => `${val} menit` },
    { key: 'sections_count', label: 'Section',
      render: (val) => val ?? 0 },
    { key: 'is_active', label: 'Status', sortable: true, badge: true,
      render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
]
</script>

<template>
    <Head title="Manajemen Ujian" />
    <DashboardLayout title="Manajemen Ujian">
        <div class="flex justify-end mb-6">
            <BaseButton :href="route('admin.exams.create')">
                <IconPlus :size="18" /> Buat Ujian Baru
            </BaseButton>
        </div>

        <DataTable
            :data="exams.data"
            :columns="columns"
            :links="exams.links"
            :meta="exams.meta"
        >
            <template #actions="{ row }">
                <Link :href="route('admin.exams.show', row.id)"
                      class="p-2 text-text-muted hover:text-secondary transition-colors" title="Detail">
                    <IconEye :size="18" />
                </Link>
            </template>
        </DataTable>
    </DashboardLayout>
</template>
