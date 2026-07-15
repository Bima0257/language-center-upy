<script setup>
import { IconTrophy } from '@tabler/icons-vue';
defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="min-h-screen bg-deep-space flex items-center justify-center p-4 md:p-10 relative overflow-hidden">
        <div class="absolute top-[-10%] right-[-10%] w-[40vw] h-[40vw] bg-secondary-container opacity-10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[30vw] h-[30vw] bg-pastel-purple opacity-5 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="w-full max-w-[1000px] bg-surface-white rounded-[32px] overflow-hidden flex flex-col md:flex-row min-h-[640px] shadow-lg z-10">
            <div class="w-full md:w-5/12 bg-surface-container-low px-6 py-6 md:p-10 flex flex-col justify-between relative overflow-hidden">
                <div>
                    <div class="flex items-center gap-3 mb-4 md:mb-10">
                        <img src="/assets/image/logo.png" alt="UPY Language Test" class="h-10 w-10 object-contain rounded-xl" />
                        <span class="text-primary text-headline-md font-bold tracking-tight">UPY Language Test</span>
                    </div>
                    <h2 class="text-text-heading text-headline-md font-bold mb-4 leading-tight">{{ title }}</h2>
                    <p class="text-text-body text-body-md max-w-xs">{{ subtitle }}</p>
                </div>

                <div class="relative mt-8 hidden md:block">
                    <div class="bg-surface-white p-4 rounded-2xl shadow-sm border border-outline-variant -rotate-2 relative z-10">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 rounded-full bg-pastel-peach flex items-center justify-center">
                                <IconTrophy class="text-tertiary-container" :size="18" />
                            </div>
                            <span class="text-label-md font-medium text-primary">Skor Target Tercapai</span>
                        </div>
                        <div class="w-full h-1 bg-track-neutral rounded-full overflow-hidden">
                            <div class="w-[85%] h-full bg-primary-container"></div>
                        </div>
                    </div>
                </div>

                <div class="text-text-muted text-label-md mt-auto hidden md:block">&copy; 2026 UPY Language Test.</div>
            </div>

            <div class="w-full md:w-7/12 p-8 md:p-14 flex flex-col justify-center">
                <div class="max-w-[360px] mx-auto w-full">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
