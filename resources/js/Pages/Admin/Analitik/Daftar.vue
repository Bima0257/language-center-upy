<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';

defineProps({
    penggunaDenganPeran: { type: Array, default: () => [] },
    sesiDenganBulan: { type: Array, default: () => [] },
    skorDenganKeterampilan: { type: Array, default: () => [] },
    tingkatLulus: { type: Array, default: () => [] },
    mahasiswaTeratas: { type: Array, default: () => [] },
    partisipasiDenganFakultas: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Analitik" />
    <TataLetakDasbor title="Analitik" subtitle="Statistik dan tren hasil ujian">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <BaseCard>
                <BarChart
                    :labels="penggunaDenganPeran.map(i => i.label)"
                    :data="penggunaDenganPeran.map(i => i.count)"
                    title="Pengguna per Peran"
                />
            </BaseCard>
            <BaseCard>
                <DoughnutChart
                    :labels="tingkatLulus.map(i => i.label)"
                    :data="tingkatLulus.map(i => i.count)"
                    title="Tingkat Kelulusan"
                />
            </BaseCard>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <BaseCard>
                <BarChart
                    :labels="sesiDenganBulan.map(i => i.label)"
                    :data="sesiDenganBulan.map(i => i.count)"
                    title="Sesi Ujian per Bulan"
                />
            </BaseCard>
            <BaseCard>
                <BarChart
                    :labels="skorDenganKeterampilan.map(i => i.label)"
                    :data="skorDenganKeterampilan.map(i => i.count)"
                    title="Rata-rata Skor per Keterampilan"
                />
            </BaseCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <BaseCard v-if="partisipasiDenganFakultas.length">
                <BarChart
                    :labels="partisipasiDenganFakultas.map(i => i.label)"
                    :data="partisipasiDenganFakultas.map(i => i.count)"
                    title="Partisipasi per Fakultas"
                />
            </BaseCard>

             <BaseCard v-if="mahasiswaTeratas.length">
                <h2 class="text-title-lg font-semibold text-primary mb-4">Top 10 Skor Tertinggi</h2>
                <div class="space-y-2">
                     <div v-for="(student, i) in mahasiswaTeratas" :key="i"
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
    </TataLetakDasbor>
</template>
