<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconPlus, IconTrash } from '@tabler/icons-vue'

const props = defineProps({
    exam: { type: Object, required: true },
    schedules: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
})

const columns = [
    { key: 'title', label: 'Nama Jadwal', sortable: true, className: 'font-medium text-primary' },
    { key: 'scheduled_start', label: 'Mulai', sortable: true,
      render: (val) => val ? new Date(val).toLocaleDateString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-' },
    { key: 'scheduled_end', label: 'Selesai', sortable: true,
      render: (val) => val ? new Date(val).toLocaleDateString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-' },
    { key: 'capacity', label: 'Kuota',
      render: (val, row) => `${row.sessions_count ?? 0}/${row.max_participants}` },
    { key: 'is_active', label: 'Status', sortable: true, badge: true,
      render: (val) => val ? 'Aktif' : 'Nonaktif' },
]

function deleteSchedule(id) {
    if (confirm('Hapus jadwal ini?')) {
        router.delete(route('admin.schedules.destroy', [props.exam.id, id]))
    }
}
</script>

<template>
    <Head title="Jadwal Ujian" />
    <DashboardLayout title="Jadwal Ujian">
        <div class="mb-6">
            <p class="text-text-body text-body-md">Ujian: <strong>{{ exam.title }}</strong></p>
        </div>
        <div class="flex justify-end mb-6">
            <Link :href="route('admin.schedules.create', exam.id)"
                  class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                <IconPlus :size="18" /> Buat Jadwal
            </Link>
        </div>

        <div class="space-y-3">
            <div v-for="schedule in schedules.data" :key="schedule.id"
                 class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary/50 transition-colors">
                <div>
                    <h3 class="text-title-lg font-semibold text-primary">{{ schedule.title }}</h3>
                    <p class="text-text-muted text-label-md">
                        {{ new Date(schedule.scheduled_start).toLocaleDateString('id-ID') }} —
                        {{ schedule.sessions_count || 0 }}/{{ schedule.max_participants }} peserta
                    </p>
                </div>
                <button @click="deleteSchedule(schedule.id)"
                        class="p-2 text-text-muted hover:text-error-red transition-colors">
                    <IconTrash :size="20" />
                </button>
            </div>
        </div>

        <div v-if="schedules.links && schedules.meta?.last_page > 1"
             class="flex justify-center mt-6 gap-1">
            <Link v-for="(link, i) in schedules.links" :key="i"
                  :href="link.url || '#'"
                  class="min-w-[36px] h-9 flex items-center justify-center rounded-full text-label-md font-medium transition-colors"
                  :class="link.active ? 'bg-primary-container text-white' : link.url ? 'text-text-body hover:bg-surface-container-low border border-outline-variant' : 'text-text-muted cursor-default'"
                  v-html="link.label" />
        </div>
    </DashboardLayout>
</template>
