<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    labels: { type: Array, default: () => [] },
    data: { type: Array, default: () => [] },
    title: { type: String, default: '' },
});

function themeColor(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

const chartData = ref(buildData());
const chartOptions = ref(buildOptions());

function buildData() {
    return {
        labels: props.labels,
        datasets: [{
            label: props.title,
            data: props.data,
            backgroundColor: themeColor('--color-primary', '#191a3b'),
            borderRadius: 6,
            maxBarThickness: 40,
        }],
    };
}

function buildOptions() {
    const gridColor = themeColor('--color-outline-variant', '#c8c5cf') + '33';
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: props.title,
                color: themeColor('--color-text-heading', '#161328'),
                font: { size: 14, weight: '600' },
                padding: { bottom: 12 },
            },
        },
        scales: {
            x: {
                ticks: { color: themeColor('--color-text-body', '#6e6e85') },
                grid: { color: gridColor },
            },
            y: {
                beginAtZero: true,
                ticks: { color: themeColor('--color-text-body', '#6e6e85'), precision: 0 },
                grid: { color: gridColor },
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
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
