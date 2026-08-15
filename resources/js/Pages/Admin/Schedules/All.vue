<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DropDown from '@/Components/Shared/DropDown.vue'
import Pagination from '@/Components/Shared/Pagination.vue'
import { IconCalendarEvent, IconTrash, IconEdit, IconArrowRight, IconPlus } from '@tabler/icons-vue'
import { computed, ref } from 'vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

const props = defineProps({
    schedules: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    exams: { type: Array, default: () => [] },
})

const selectedExamId = ref('')

const canCreate = computed(() => !!selectedExamId.value)

function createSchedule() {
    if (!selectedExamId.value) return
    router.get(route('admin.schedules.create', selectedExamId.value))
}

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
        <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
            <div class="w-80">
                <DropDown
                    v-model="selectedExamId"
                    :options="exams"
                    label="Pilih Ujian"
                    placeholder="Pilih ujian untuk membuat jadwal"
                    option-label="title"
                    option-value="id"
                />
            </div>
            <BaseButton @click="createSchedule" :disabled="!canCreate">
                <IconPlus :size="18" /> Buat Jadwal
            </BaseButton>
        </div>

        <div v-if="schedules.data.length === 0"
             class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Belum Ada Jadwal</h2>
            <p class="text-text-body text-body-md">Pilih ujian di atas, lalu klik "Buat Jadwal".</p>
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

        <Pagination :links="schedules.links" :total="schedules.meta?.total ?? 0" :per-page="schedules.meta?.per_page ?? 0" />
    </DashboardLayout>
</template>
