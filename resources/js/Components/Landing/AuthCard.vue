<script setup>
import { IconUsers, IconBooks, IconTrendingUp } from '@tabler/icons-vue';

defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="h-dvh flex flex-col md:flex-row overflow-hidden">
        <div class="hidden md:flex md:w-5/12 bg-primary-container px-6 py-6 md:p-10 flex-col justify-between shrink-0 md:border-r border-white/5">

            <div>
                <div class="flex items-center gap-3 mb-4 md:mb-8">
                    <img src="/assets/image/logo-white.png" alt="UPY Language Test" class="h-10 w-10 object-contain rounded-xl" />
                    <span class="text-white/90 text-headline-md font-bold tracking-tight">UPY Language Test</span>
                </div>
                <h2 class="text-white/90 text-headline-md font-bold mb-3 leading-tight">{{ title }}</h2>
                <p class="text-white/50 text-body-md max-w-xs">{{ subtitle }}</p>

                <div class="hidden md:flex flex-col gap-3 mt-8">
                    <div class="bg-white/5 rounded-xl p-4">
                        <div class="flex items-center gap-2.5 mb-1.5">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <IconUsers class="text-white/80" :size="16" />
                            </div>
                            <span class="text-white text-title-lg font-bold">10,000+</span>
                        </div>
                        <p class="text-white/80 text-label-md font-medium mb-0.5">Peserta Aktif</p>
                        <p class="text-white/40 text-body-md">Bergabung dan raih skor impian bersama ribuan peserta lainnya.</p>
                    </div>

                    <div class="bg-white/5 rounded-xl p-4">
                        <div class="flex items-center gap-2.5 mb-1.5">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <IconBooks class="text-white/80" :size="16" />
                            </div>
                            <span class="text-white text-title-lg font-bold">5,000+</span>
                        </div>
                        <p class="text-white/80 text-label-md font-medium mb-0.5">Soal Latihan</p>
                        <p class="text-white/40 text-body-md">Materi komprehensif mencakup Reading, Listening, Speaking, dan Writing.</p>
                    </div>

                    <div class="bg-white/5 rounded-xl p-4">
                        <div class="flex items-center gap-2.5 mb-1.5">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <IconTrendingUp class="text-white/80" :size="16" />
                            </div>
                            <span class="text-white text-title-lg font-bold">85+</span>
                        </div>
                        <p class="text-white/80 text-label-md font-medium mb-0.5">Rata-rata Skor</p>
                        <p class="text-white/40 text-body-md">Peningkatan skor signifikan yang dicapai oleh para peserta kami.</p>
                    </div>
                </div>
            </div>

            <div class="text-white/30 text-label-md mt-auto hidden md:block">&copy; 2026 UPY Language Test.</div>
        </div>

        <div class="flex-1 min-h-0 bg-surface-white p-6 md:p-10 flex flex-col justify-start overflow-y-auto">
            <div class="max-w-[520px] mx-auto w-full bg-white shadow-sm rounded-3xl px-6 py-12 md:px-8 md:py-16">
                <slot />
            </div>
        </div>
    </div>
</template>
