<script setup>
import TimerDisplay from '@/Components/Exam/TimerDisplay.vue';
import { IconUser } from '@tabler/icons-vue';

defineProps({
    session: { type: Object, required: true },
    remainingSeconds: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    isWarning: { type: Boolean, default: false },
    isDanger: { type: Boolean, default: false },
});
</script>

<template>
    <div class="min-h-screen bg-[#FAFAFA]">
        <header class="fixed top-0 inset-x-0 z-50 h-14 bg-white border-b border-outline-variant flex items-center px-6 gap-4 shadow-sm">
            <div class="bg-primary-container rounded-lg w-8 h-8 flex items-center justify-center">
                <span class="text-white font-bold text-xs">UPY</span>
            </div>
            <span class="text-label-md text-text-muted truncate">{{ session.user?.name || 'Peserta' }}</span>
            <div class="ml-auto flex items-center gap-4">
                <TimerDisplay :remaining="remainingSeconds" :minutes="minutes" :seconds="seconds"
                              :is-warning="isWarning" :is-danger="isDanger" />
                <span class="inline-flex items-center gap-1.5 text-label-md">
                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                    Online → Daring
                </span>
            </div>
        </header>
        <main class="pt-14 h-screen overflow-hidden flex flex-col">
            <div class="flex-1 overflow-hidden flex">
                <div class="flex-1 overflow-hidden">
                    <slot />
                </div>
                <aside v-if="$slots.sidebar" class="w-16 bg-white border-l border-outline-variant flex flex-col items-center py-4 gap-2">
                    <slot name="sidebar" />
                </aside>
            </div>
            <slot name="footer" />
        </main>
    </div>
</template>
