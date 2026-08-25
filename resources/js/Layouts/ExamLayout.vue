<script setup>
import TimerDisplay from '@/Components/Exam/TimerDisplay.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

defineProps({
    session: { type: Object, required: true },
    title: { type: String, default: '' },
    remainingSeconds: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    isWarning: { type: Boolean, default: false },
    isDanger: { type: Boolean, default: false },
    showTimer: { type: Boolean, default: true },
});
</script>

<template>
    <div class="min-h-screen bg-[#F1F2F6]">
        <header
            class="fixed top-0 inset-x-0 z-50 h-16 bg-primary-container flex items-center px-6 gap-5"
        >
            <div class="flex items-center gap-3 min-w-0">
                <template v-if="showTimer">
                    <TimerDisplay
                        :remaining="remainingSeconds"
                        :minutes="minutes"
                        :seconds="seconds"
                        :is-warning="isWarning"
                        :is-danger="isDanger"
                        theme="dark"
                    />
                    <div class="h-6 w-px bg-white/20"></div>
                </template>
                <span
                    class="font-body-md text-body-md text-white font-bold truncate"
                >{{ title }}</span
                >
            </div>
            <div class="ml-auto flex items-center gap-4">
                <div
                    class="flex items-center gap-2 text-white/80 text-label-md"
                >
                    <span
                        class="w-2 h-2 rounded-full bg-green-400 inline-block animate-pulse"
                    ></span>
                    <span>{{ session.user?.name || 'Peserta' }}</span>
                </div>
            </div>
        </header>
        <main class="pt-16 h-screen overflow-hidden flex flex-col">
            <div class="flex-1 overflow-hidden flex">
                <div class="flex-1 overflow-hidden">
                    <slot />
                </div>
                <aside
                    v-if="$slots.sidebar"
                    class="w-[72px] bg-white border-l border-outline-variant flex flex-col items-center py-5 gap-2"
                >
                    <slot name="sidebar" />
                </aside>
            </div>
            <slot name="footer" />
        </main>
        <ConfirmDialog />
    </div>
</template>
