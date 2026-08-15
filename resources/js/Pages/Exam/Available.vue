<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconClipboardCheck } from '@tabler/icons-vue';

defineProps({
    slots: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Ujian Tersedia" />
    <DashboardLayout title="Ujian Tersedia">
        <div v-if="slots.length === 0" class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Tidak Ada Sesi Tersedia</h2>
            <p class="text-text-body text-body-md">Saat ini belum ada sesi ujian yang dapat diikuti.</p>
        </div>
        <div v-else class="space-y-4">
            <BaseCard
                v-for="slot in slots"
                :key="slot.id"
                class="flex items-center justify-between"
            >
                <div>
                    <h3 class="text-title-lg font-semibold text-primary">{{ slot.schedule?.exam?.title }}</h3>
                    <p class="text-text-muted text-label-md">
                        {{ slot.schedule?.title }} — Sesi {{ slot.start_time?.substring(0, 5) }}–{{ slot.end_time?.substring(0, 5) }} WIB ({{ slot.schedule?.exam?.duration_minutes }} menit)
                    </p>
                    <p class="text-text-muted text-xs mt-1">{{ slot.max_participants }} kuota — toleransi {{ slot.late_tolerance_minutes ?? 15 }} menit</p>
                </div>
                <BaseButton :href="route('exam.pre-check', slot.id)">
                    <IconClipboardCheck :size="18" /> Ikuti Ujian
                </BaseButton>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
