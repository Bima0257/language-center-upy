<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconPlus } from '@tabler/icons-vue'

const props = defineProps({
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
]

function goToExam(exam) {
    router.visit(route('admin.exams.show', exam.id))
}
</script>

<template>
    <Head title="Manajemen Ujian" />
    <DashboardLayout title="Manajemen Ujian">
        <div class="flex justify-end mb-6">
            <Link :href="route('admin.exams.create')"
                  class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                <IconPlus :size="18" /> Buat Ujian Baru
            </Link>
        </div>

        <DataTable
            :data="exams.data"
            :columns="columns"
            :links="exams.links"
            :meta="exams.meta"
            :row-link="goToExam"
        />
    </DashboardLayout>
</template>
