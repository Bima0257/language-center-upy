<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import { IconPlus, IconTrash, IconEdit } from '@tabler/icons-vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

const props = defineProps({
    exam: { type: Object, required: true },
    schedules: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
})

async function deleteSchedule(id) {
    if (await confirm.confirm('Hapus jadwal ini?')) {
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
            <BaseButton :href="route('admin.schedules.create', exam.id)">
                <IconPlus :size="18" /> Buat Jadwal
            </BaseButton>
        </div>

        <div class="space-y-3">
            <BaseCard
                v-for="schedule in schedules.data"
                :key="schedule.id"
                padding="p-5"
                class="flex items-center justify-between hover:border-secondary/50 transition-colors"
            >
                <div>
                    <h3 class="text-title-lg font-semibold text-primary">{{ schedule.title }}</h3>
                    <p class="text-text-muted text-label-md">
                        {{ new Date(schedule.scheduled_start).toLocaleDateString('id-ID') }} —
                        {{ schedule.sessions_count || 0 }}/{{ schedule.max_participants }} peserta —
                        toleransi {{ schedule.late_tolerance_minutes ?? 15 }} menit
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <Link :href="route('admin.schedules.edit', [exam.id, schedule.id])"
                          class="p-2 text-text-muted hover:text-secondary transition-colors">
                        <IconEdit :size="20" />
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
