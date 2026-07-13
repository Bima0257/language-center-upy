<script setup>
import { Link } from '@inertiajs/vue3';
import { IconArrowRight, IconBook2, IconMicrophone, IconHeadphones, IconPencil } from '@tabler/icons-vue';
</script>

<template>
    <section class="max-w-7xl mx-auto mb-12 px-4 md:px-0">
        <div class="bg-primary-container rounded-2xl p-8 md:p-12 flex flex-col md:flex-row items-center justify-between overflow-hidden relative">
            <div class="z-10 text-center md:text-left md:max-w-[50%]">
                <h1 class="text-white text-3xl md:text-4xl font-bold leading-tight mb-4">
                    Kuasai TOEFL iBT.<br />Raih Masa Depanmu.
                </h1>
                <p class="text-[#B9B8CE] text-body-md mb-8 max-w-sm mx-auto md:mx-0">
                    Cara cerdas untuk berlatih dan sukses dengan metodologi berpengalaman.
                </p>
                <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                    <Link href="#features" class="bg-surface-white text-primary px-8 py-3 rounded-full font-bold text-title-lg active:scale-95 transition-all shadow-lg shadow-black/20 hover:opacity-90">
                        Jelajahi Materi
                    </Link>
                    <Link href="#methodology" class="text-white flex items-center gap-2 font-bold px-4 py-3 rounded-full transition-all hover:bg-white/10">
                        Lihat Metode
                        <IconArrowRight :size="20" />
                    </Link>
                </div>
            </div>

            <div class="hidden md:flex relative w-full md:w-[45%] h-64 items-center justify-center">
                <div class="grid grid-cols-2 gap-4 w-full h-full p-4">
                    <div class="bg-pastel-purple rounded-2xl flex flex-col items-center justify-center p-4 -rotate-3 hover:rotate-0 transition-transform duration-500">
                        <IconBook2 class="text-secondary" :size="36" stroke="1.5" />
                        <span class="text-primary font-bold text-xs mt-2">Membaca</span>
                    </div>
                    <div class="bg-pastel-blue rounded-2xl flex flex-col items-center justify-center p-4 translate-y-8 rotate-2 hover:rotate-0 transition-transform duration-500">
                        <IconMicrophone class="text-secondary" :size="36" stroke="1.5" />
                        <span class="text-primary font-bold text-xs mt-2">Berbicara</span>
                    </div>
                    <div class="bg-pastel-peach rounded-2xl flex flex-col items-center justify-center p-4 -translate-y-4 -rotate-6 hover:rotate-0 transition-transform duration-500">
                        <IconHeadphones class="text-secondary" :size="36" stroke="1.5" />
                        <span class="text-primary font-bold text-xs mt-2">Mendengar</span>
                    </div>
                    <div class="bg-white/20 rounded-2xl border border-white/20 flex flex-col items-center justify-center p-4 translate-y-4 rotate-3 hover:rotate-0 transition-transform duration-500">
                        <IconPencil class="text-white" :size="36" stroke="1.5" />
                        <span class="text-white font-bold text-xs mt-2">Menulis</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
