<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import { IconCalendarEvent, IconTrash, IconEdit, IconArrowRight } from '@tabler/icons-vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

const props = defineProps({
    schedules: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
})

function formatDate(value) {
    return value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-'
}

async function deleteSchedule(id) {
    if (await confirm.confirm('Hapus jadwal ini?')) {
        router.delete(route('admin.schedules.destroy', [props.schedules.data.find((s) => s.id === id).exam_id, id]))
    }
}
</script>

<template>
    <Head title="Penjadwalan Ujian" />
    <DashboardLayout title="Penjadwalan Ujian">
        <div v-if="schedules.data.length === 0"
             class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Belum Ada Jadwal</h2>
            <p class="text-text-body text-body-md">Buat jadwal melalui halaman ujian terkait.</p>
        </div>

        <div v-else class="space-y-3">
            <BaseCard
                v-for="schedule in schedules.data"
                :key="schedule.id"
                padding="p-5"
                class="flex items-center justify-between hover:border-secondary/50 transition-colors"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <IconCalendarEvent class="text-secondary shrink-0" :size="18" />
                        <h3 class="text-title-lg font-semibold text-primary truncate">{{ schedule.title }}</h3>
                    </div>
                    <p class="text-text-body text-label-md">
                        {{ schedule.exam?.title }} —
                        {{ formatDate(schedule.scheduled_start) }} s/d {{ formatDate(schedule.scheduled_end) }}
                    </p>
                    <p class="text-text-muted text-xs mt-1">
                        {{ schedule.sessions_count || 0 }}/{{ schedule.max_participants }} peserta —
                        toleransi {{ schedule.late_tolerance_minutes ?? 15 }} menit —
                        <span :class="schedule.is_active ? 'text-green-600' : 'text-error-red'">
                            {{ schedule.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <Link :href="route('admin.schedules.edit', [schedule.exam_id, schedule.id])"
                          class="p-2 text-text-muted hover:text-secondary transition-colors">
                        <IconEdit :size="20" />
                    </Link>
                    <Link :href="route('admin.schedules.index', schedule.exam_id)"
                          class="flex items-center gap-1 px-3 py-2 rounded-full text-label-md font-medium text-secondary hover:bg-surface-container-low transition-colors">
                        Kelola <IconArrowRight :size="16" />
                    </Link>
                    <button @click="deleteSchedule(schedule.id)"
                            class="p-2 text-text-muted hover:text-error-red transition-colors">
                        <IconTrash :size="20" />
                    </button>
                </div>
            </BaseCard>
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
