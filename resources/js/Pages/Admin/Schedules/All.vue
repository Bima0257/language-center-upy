<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue'
import DropDown from '@/Components/Shared/DropDown.vue'
import Pagination from '@/Components/Shared/Pagination.vue'
import { IconCalendarEvent, IconTrash, IconEdit, IconArrowRight, IconPlus, IconClock } from '@tabler/icons-vue'
import { computed, ref } from 'vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

defineProps({
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
    return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'
}

async function destroy(schedule) {
    if (schedule.slots_count > 0) return
    if (!await confirm.confirm(`Hapus periode "${schedule.title}"?`)) return
    router.delete(route('admin.schedules.destroy', schedule.id), { preserveScroll: true })
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
                    placeholder="Pilih ujian untuk membuat periode"
                    option-label="title"
                    option-value="id"
                />
            </div>
            <BaseButton @click="createSchedule" :disabled="!canCreate">
                <IconPlus :size="18" /> Buat Periode Ujian
            </BaseButton>
        </div>

        <div v-if="schedules.data.length === 0"
             class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Belum Ada Periode Ujian</h2>
            <p class="text-text-body text-body-md">Pilih ujian di atas, lalu klik "Buat Periode Ujian".</p>
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
                        {{ schedule.exam?.title }} — {{ formatDate(schedule.start_date) }} s/d {{ formatDate(schedule.end_date) }}
                    </p>
                    <p class="text-text-muted text-xs mt-1">
                        <span class="inline-flex items-center gap-1"><IconClock :size="14" /> {{ schedule.slots_count || 0 }} sesi</span>
                        —
                        <span :class="schedule.is_active ? 'text-green-600 dark:text-green-400' : 'text-error-red'">
                            {{ schedule.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <Link :href="route('admin.schedules.show', schedule.id)"
                          class="flex items-center gap-1 px-3 py-2 rounded-full text-label-md font-medium text-secondary hover:bg-surface-container-low transition-colors">
                        Kelola Sesi <IconArrowRight :size="16" />
                    </Link>
                    <Link :href="route('admin.schedules.edit', schedule.id)"
                          class="p-2 text-text-muted hover:text-secondary transition-colors">
                        <IconEdit :size="20" />
                    </Link>
                    <button @click="destroy(schedule)"
                            :disabled="schedule.slots_count > 0"
                            :title="schedule.slots_count > 0 ? 'Tidak bisa dihapus — sudah punya sesi' : 'Hapus periode'"
                            class="p-2 transition-colors"
                            :class="schedule.slots_count > 0
                                ? 'text-outline-variant cursor-not-allowed'
                                : 'text-text-muted hover:text-error-red'">
                        <IconTrash :size="20" />
                    </button>
                </div>
            </BaseCard>
        </div>

        <Pagination :links="schedules.links" :total="schedules.meta?.total ?? 0" :per-page="schedules.meta?.per_page ?? 0" />
    </DashboardLayout>
</template>
