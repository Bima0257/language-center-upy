<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement);

const props = defineProps({
    labels: { type: Array, default: () => [] },
    data: { type: Array, default: () => [] },
    title: { type: String, default: '' },
});

const PALETTE = [
    '--color-primary',
    '--color-secondary',
    '--color-error-red',
    '--color-pastel-purple',
    '--color-pastel-blue',
    '--color-pastel-peach',
];

function themeColor(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

const chartData = ref(buildData());
const chartOptions = ref(buildOptions());

function buildData() {
    const colors = PALETTE.map((c) => themeColor(c, '#5647c8'));

    return {
        labels: props.labels,
        datasets: [{
            data: props.data,
            backgroundColor: colors,
            hoverOffset: 8,
            borderWidth: 2,
            borderColor: themeColor('--color-surface-white', '#ffffff'),
        }],
    };
}

function buildOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: themeColor('--color-text-body', '#6e6e85'),
                    boxWidth: 12,
                    padding: 12,
                    font: { size: 12 },
                },
            },
            title: {
                display: true,
                text: props.title,
                color: themeColor('--color-text-heading', '#161328'),
                font: { size: 14, weight: '600' },
                padding: { bottom: 8 },
            },
        },
    };
}

function refresh() {
    chartData.value = buildData();
    chartOptions.value = buildOptions();
}

onMounted(() => document.addEventListener('dashboard-theme-changed', refresh));
onUnmounted(() => document.removeEventListener('dashboard-theme-changed', refresh));
</script>

<template>
    <div class="h-64">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
