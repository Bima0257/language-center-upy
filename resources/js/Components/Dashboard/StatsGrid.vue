<script setup>
import StatsCard from '@/Components/Dashboard/StatsCard.vue';

const stats = [
    { value: 85, label: 'Skor Rata-rata' },
    { value: 3, label: 'Try Out Selesai' },
    { value: 24, label: 'Streak Hari' },
    { value: 7, label: 'Jam Belajar' },
];
</script>

<template>
    <div>
        <h3 class="text-headline-md font-bold text-primary mb-6">Statistik</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <StatsCard v-for="stat in stats" :key="stat.label" :value="stat.value" :label="stat.label" />
        </div>
    </div>
</template>
