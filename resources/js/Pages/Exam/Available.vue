<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconClipboardCheck } from '@tabler/icons-vue';

defineProps({
    schedules: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Ujian Tersedia" />
    <DashboardLayout title="Ujian Tersedia">
        <div v-if="schedules.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Tidak Ada Ujian Tersedia</h2>
            <p class="text-text-body text-body-md">Saat ini belum ada ujian yang dapat diikuti.</p>
        </div>
        <div v-else class="space-y-4">
            <div v-for="schedule in schedules" :key="schedule.id"
                 class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 flex items-center justify-between">
                <div>
                    <h3 class="text-title-lg font-semibold text-primary">{{ schedule.exam?.title }}</h3>
                    <p class="text-text-muted text-label-md">{{ schedule.title }} — {{ schedule.exam?.duration_minutes }} menit</p>
                </div>
                <Link :href="route('exam.pre-check', schedule.id)"
                      class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                    <IconClipboardCheck :size="18" /> Ikuti Ujian
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
