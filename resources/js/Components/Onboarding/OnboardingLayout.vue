<script setup>
import { Link } from '@inertiajs/vue3';
import { IconCircleCheck, IconCircleDot, IconLogout } from '@tabler/icons-vue';

defineProps({
    currentStep: { type: Number, default: 2 },
});

const steps = [
    { label: 'Buat Akun', icon: IconCircleCheck },
    { label: 'Verifikasi Email', icon: IconCircleCheck },
    { label: 'Verifikasi Identitas', icon: IconCircleDot },
];
</script>

<template>
    <div class="min-h-screen md:h-screen md:overflow-hidden bg-deep-space p-0 md:p-10">
        <div class="flex flex-col h-full max-w-[1440px] mx-auto overflow-hidden md:rounded-3xl shadow-standard">
            <div class="bg-white px-6 md:px-10 py-5 flex items-center justify-between shrink-0 border-b border-outline-variant/30">
                <div class="flex items-center gap-2">
                    <template v-for="(step, i) in steps" :key="i">
                        <div class="flex items-center gap-2">
                            <component :is="step.icon" :size="22" stroke="1.5"
                                       :class="i < currentStep ? 'text-green-500' : i === currentStep ? 'text-primary-container' : 'text-text-muted'" />
                            <span class="text-sm md:text-label-md font-medium"
                                  :class="i < currentStep ? 'text-green-600' : i === currentStep ? 'text-primary font-bold' : 'text-text-muted'">
                                {{ step.label }}
                            </span>
                        </div>
                        <IconCircleCheck v-if="i < steps.length - 1" class="text-outline-variant mx-1 md:mx-3" :size="12" stroke="2" />
                    </template>
                </div>
                <Link :href="route('logout')" method="post" as="button"
                      class="flex items-center gap-2 text-text-muted hover:text-error-red transition-colors text-sm font-medium">
                    <IconLogout :size="18" stroke="1.5" />
                    <span class="hidden md:inline">Keluar</span>
                </Link>
            </div>
            <main class="flex-1 overflow-y-auto bg-[#f4f4f7]">
                <slot />
            </main>
        </div>
    </div>
</template>
