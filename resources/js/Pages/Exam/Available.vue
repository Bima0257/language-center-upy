<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconClipboardCheck } from '@tabler/icons-vue';

defineProps({
    schedules: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Ujian Tersedia" />
    <DashboardLayout title="Ujian Tersedia">
        <div v-if="schedules.length === 0" class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Tidak Ada Ujian Tersedia</h2>
            <p class="text-text-body text-body-md">Saat ini belum ada ujian yang dapat diikuti.</p>
        </div>
        <div v-else class="space-y-4">
            <BaseCard
                v-for="schedule in schedules"
                :key="schedule.id"
                class="flex items-center justify-between"
            >
                <div>
                    <h3 class="text-title-lg font-semibold text-primary">{{ schedule.exam?.title }}</h3>
                    <p class="text-text-muted text-label-md">{{ schedule.title }} — {{ schedule.exam?.duration_minutes }} menit</p>
                </div>
                <BaseButton :href="route('exam.pre-check', schedule.id)">
                    <IconClipboardCheck :size="18" /> Ikuti Ujian
                </BaseButton>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
