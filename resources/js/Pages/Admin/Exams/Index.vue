<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconPlus, IconEye, IconTrash } from '@tabler/icons-vue'
import { useConfirm } from '@/Composables/useConfirm'

defineProps({
    exams: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
})

const confirm = useConfirm()

async function destroy(exam) {
    if (exam.schedules_exists) return
    if (!await confirm.confirm(`Hapus ujian "${exam.title}"? Tindakan ini tidak bisa dibatalkan.`)) return
    router.delete(route('admin.exams.destroy', exam.id), { preserveScroll: true })
}

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
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.exams.show', row.id)"
                          class="p-2 text-text-muted hover:text-secondary transition-colors" title="Detail">
                        <IconEye :size="18" />
                    </Link>
                    <button
                        @click="destroy(row)"
                        :disabled="row.schedules_exists"
                        :title="row.schedules_exists ? 'Tidak bisa dihapus — sudah terjadwal' : 'Hapus ujian'"
                        class="p-2 transition-colors"
                        :class="row.schedules_exists
                            ? 'text-outline-variant cursor-not-allowed'
                            : 'text-text-muted hover:text-error-red'"
                    >
                        <IconTrash :size="18" />
                    </button>
                </div>
            </template>
        </DataTable>
    </DashboardLayout>
</template>
