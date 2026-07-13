<script setup>
import { IconBook2, IconHeadphones, IconMicrophone } from '@tabler/icons-vue';

const lessons = [
    { label: 'Tugas Membaca', icon: IconBook2, count: '5 / 9 sesi', progress: 55, bg: 'bg-pastel-purple' },
    { label: 'Latihan Mendengar', icon: IconHeadphones, count: '2 / 5 sesi', progress: 40, bg: 'bg-pastel-blue' },
    { label: 'Latihan Berbicara', icon: IconMicrophone, count: '7 / 10 sesi', progress: 70, bg: 'bg-pastel-peach' },
];
</script>

<template>
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-10">
        <div class="xl:col-span-12 bg-primary-container rounded-3xl p-8 flex flex-col md:flex-row items-center gap-8 text-white relative overflow-hidden">
            <div class="flex-1 z-10">
                <p class="text-body-md opacity-70 mb-2">Halo, {{ $page.props.auth.user.name }}!</p>
                <h3 class="text-3xl md:text-4xl font-bold max-w-md leading-tight mb-6">
                    Kamu sudah menyelesaikan 8 sesi latihan minggu ini
                </h3>
                <button class="bg-white text-primary px-8 py-3 rounded-full font-semibold text-title-lg hover:bg-surface-container transition-all active:scale-95">
                    Lihat Semua
                </button>
            </div>

            <div class="flex gap-4 overflow-x-auto w-full md:w-auto pb-2 z-10 scrollbar-hide">
                <div v-for="lesson in lessons" :key="lesson.label"
                     class="min-w-[200px] bg-white rounded-2xl p-4 flex flex-col shadow-lg flex-shrink-0">
                    <div class="w-full h-24 rounded-xl mb-4 flex items-center justify-center relative overflow-hidden"
                         :class="lesson.bg">
                        <component :is="lesson.icon" class="text-primary" :size="36" stroke="1.5" />
                    </div>
                    <h4 class="text-title-lg font-semibold text-primary leading-tight mb-1">{{ lesson.label }}</h4>
                    <p class="text-label-md font-medium text-text-muted mb-4">{{ lesson.count }}</p>
                    <div class="h-1.5 w-full bg-track-neutral rounded-full">
                        <div class="h-full bg-primary rounded-full" :style="{ width: lesson.progress + '%' }"></div>
                    </div>
                </div>
            </div>

            <div class="absolute -right-20 -top-20 w-80 h-80 bg-secondary-container/20 blur-[100px] rounded-full"></div>
            <div class="absolute -left-20 -bottom-20 w-60 h-60 bg-pastel-purple/10 blur-[80px] rounded-full"></div>
        </div>
    </section>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
