<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';

defineProps({
    usersByRole: { type: Array, default: () => [] },
    sessionsByMonth: { type: Array, default: () => [] },
    scoresBySkill: { type: Array, default: () => [] },
    passFailRate: { type: Array, default: () => [] },
    topStudents: { type: Array, default: () => [] },
    participationByFaculty: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Analytics" />
    <DashboardLayout title="Analytics">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <BaseCard>
                <BarChart
                    :labels="usersByRole.map(i => i.label)"
                    :data="usersByRole.map(i => i.count)"
                    title="User per Role"
                />
            </BaseCard>
            <BaseCard>
                <DoughnutChart
                    :labels="passFailRate.map(i => i.label)"
                    :data="passFailRate.map(i => i.count)"
                    title="Tingkat Kelulusan"
                />
            </BaseCard>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <BaseCard>
                <BarChart
                    :labels="sessionsByMonth.map(i => i.label)"
                    :data="sessionsByMonth.map(i => i.count)"
                    title="Sesi Ujian per Bulan"
                />
            </BaseCard>
            <BaseCard>
                <BarChart
                    :labels="scoresBySkill.map(i => i.label)"
                    :data="scoresBySkill.map(i => i.count)"
                    title="Rata-rata Skor per Skill"
                />
            </BaseCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <BaseCard v-if="participationByFaculty.length">
                <BarChart
                    :labels="participationByFaculty.map(i => i.label)"
                    :data="participationByFaculty.map(i => i.count)"
                    title="Partisipasi per Fakultas"
                />
            </BaseCard>

            <BaseCard v-if="topStudents.length">
                <h2 class="text-title-lg font-semibold text-primary mb-4">Top 10 Skor Tertinggi</h2>
                <div class="space-y-2">
                    <div v-for="(student, i) in topStudents" :key="i"
                        class="flex items-center justify-between py-2 border-b border-outline-variant/20 last:border-0">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-surface-container flex items-center justify-center text-label-md font-bold text-text-muted">
                                {{ i + 1 }}
                            </span>
                            <div>
                                <p class="text-body-md text-primary font-medium">{{ student.name }}</p>
                                <p class="text-label-md text-text-muted">{{ student.date }}</p>
                            </div>
                        </div>
                        <span class="text-title-lg font-bold text-secondary">{{ student.score }}</span>
                    </div>
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
