<script setup>
import { IconExternalLink, IconListCheck, IconBrain, IconSchool, IconChartBar, IconMessages, IconBroadcast } from '@tabler/icons-vue';
import { h } from 'vue';

const features = [
    {
        title: 'Try Out Lengkap',
        description: 'Simulasi ujian realistis yang mereplikasi lingkungan tes TOEFL iBT sesungguhnya.',
        icon: h(IconListCheck),
        bgClass: 'bg-pastel-blue',
    },
    {
        title: 'Penilaian AI untuk Speaking',
        description: 'Umpan balik instan dan objektif pada pengucapan dan kefasihan bertenaga AI.',
        icon: h(IconBrain),
        bgClass: 'bg-pastel-purple',
    },
    {
        title: 'Pelajaran Skill',
        description: 'Modul kurasi yang berfokus pada tata bahasa, kosakata, dan teknik membaca akademik.',
        icon: h(IconSchool),
        bgClass: 'bg-pastel-peach',
    },
    {
        title: 'Pelacakan Kemajuan',
        description: 'Dasbor visual yang menyoroti kekuatan dan area yang perlu perbaikan.',
        icon: h(IconChartBar),
        bgClass: 'bg-surface-container-low',
    },
    {
        title: 'Umpan Balik Ahli',
        description: 'Wawasan personal dari instruktur bersertifikat untuk tulisan dan respons kompleks.',
        icon: h(IconMessages),
        bgClass: 'bg-pastel-blue',
    },
    {
        title: 'Sesi Langsung',
        description: 'Lokakarya kelompok interaktif harian untuk membahas bagian tes tersulit bersama.',
        icon: h(IconBroadcast),
        bgClass: 'bg-pastel-purple',
    },
];
</script>

<template>
    <section id="features" class="max-w-7xl mx-auto mb-16 px-4 md:px-0">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-text-heading text-headline-md font-bold">
                    Kenapa UPY Language Test?
                </h2>
                <p class="text-text-muted text-body-md">
                    Alat canggih untuk kesuksesan TOEFL.
                </p>
            </div>
            <a href="#" class="text-primary font-bold flex items-center gap-1 hover:underline text-sm">
                Lihat semua fitur
                <IconExternalLink :size="16" />
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-card-gap">
            <div v-for="feature in features" :key="feature.title"
                 class="bg-surface-white p-inner-padding rounded-2xl shadow-standard hover:-translate-y-1 transition-all cursor-pointer group">
                <div class="rounded-xl flex items-center justify-center mb-6 w-12 h-12" :class="feature.bgClass">
                    <component :is="feature.icon" class="text-primary group-hover:scale-110 transition-transform" :size="24" stroke="1.5" />
                </div>
                <h3 class="text-text-heading text-title-lg font-semibold mb-2">{{ feature.title }}</h3>
                <p class="text-text-body text-body-md">{{ feature.description }}</p>
            </div>
        </div>
    </section>
</template>
