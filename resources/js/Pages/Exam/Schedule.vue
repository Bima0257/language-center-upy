<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCalendarEvent, IconClock, IconUsers } from '@tabler/icons-vue';

defineProps({
    schedules: { type: Array, default: () => [] },
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatTime(time) {
    if (!time) return '-';
    return time.substring(0, 5);
}

function slotStatus(slot) {
    const now = new Date();
    const slotDate = new Date(slot.date);
    const [h, m] = (slot.start_time || '00:00').split(':').map(Number);
    slotDate.setHours(h, m, 0, 0);

    const endSlot = new Date(slotDate);
    const [eh, em] = (slot.end_time || '23:59').split(':').map(Number);
    endSlot.setHours(eh, em, 0, 0);

    if (now >= slotDate && now <= endSlot) return 'active';
    if (now > endSlot) return 'past';
    return 'upcoming';
}

function slotStatusLabel(slot) {
    const s = slotStatus(slot);
    return { active: 'Berlangsung', past: 'Selesai', upcoming: 'Akan Datang' }[s];
}
</script>

<template>
    <Head title="Jadwal Ujian" />
    <DashboardLayout title="Jadwal Ujian">
        <div v-if="schedules.length === 0" class="text-center py-16">
            <IconCalendarEvent class="mx-auto text-text-muted mb-4" :size="48" stroke="1.5" />
            <p class="text-text-muted text-body-md">Belum ada jadwal ujian yang tersedia.</p>
        </div>

        <div class="space-y-6">
            <BaseCard v-for="schedule in schedules" :key="schedule.id">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-title-lg font-bold text-primary">{{ schedule.title }}</h2>
                        <p class="text-text-muted text-body-md mt-1">{{ schedule.exam?.title || '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2 text-label-md text-text-muted">
                        <IconCalendarEvent :size="16" />
                        <span>{{ formatDate(schedule.start_date) }} — {{ formatDate(schedule.end_date) }}</span>
                    </div>
                </div>

                <div v-if="schedule.slots?.length" class="divide-y divide-outline-variant/20">
                    <div
                        v-for="slot in schedule.slots"
                        :key="slot.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-surface-container-low flex items-center justify-center shrink-0">
                                <IconClock class="text-secondary" :size="20" />
                            </div>
                            <div>
                                <p class="font-medium text-primary">
                                    {{ new Date(slot.date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </p>
                                <p class="text-label-md text-text-muted">
                                    {{ formatTime(slot.start_time) }} — {{ formatTime(slot.end_time) }}
                                    · Toleransi keterlambatan {{ slot.late_tolerance_minutes }} menit
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 text-label-md text-text-muted">
                                <IconUsers :size="14" />
                                <span>Maks. {{ slot.max_participants }} peserta</span>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-label-md font-medium"
                                :class="{
                                    'bg-green-100 text-green-700': slotStatus(slot) === 'active',
                                    'bg-gray-100 text-gray-500': slotStatus(slot) === 'past',
                                    'bg-pastel-blue/50 text-blue-700': slotStatus(slot) === 'upcoming',
                                }"
                            >
                                {{ slotStatusLabel(slot) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-4 text-center text-text-muted text-body-md">
                    Belum ada slot tersedia untuk jadwal ini.
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
