<script setup>
import TimerDisplay from '@/Layouts/Ujian/Components/TimerDisplay.vue';
import ConfirmDialog from '@/Components/Shared/ConfirmDialog.vue';
import { IconMoon, IconSun } from '@tabler/icons-vue';

defineProps({
    sesiUjian: { type: Object, required: true },
    title: { type: String, default: '' },
    remainingSeconds: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    isWarning: { type: Boolean, default: false },
    isDanger: { type: Boolean, default: false },
    showTimer: { type: Boolean, default: true },
    softHeader: { type: Boolean, default: false },
    showThemeToggle: { type: Boolean, default: false },
    themeMode: { type: String, default: 'light' },
});

defineEmits(['toggle-theme']);
</script>

<template>
    <div class="min-h-screen bg-canvas">
        <header
            class="fixed top-0 inset-x-0 z-50 flex h-16 items-center gap-5 px-6"
            :class="softHeader
                ? 'border-b border-outline-variant/60 bg-surface-container-low shadow-sm'
                : 'bg-primary-container'"
        >
            <div class="flex items-center gap-3 min-w-0">
                <template v-if="showTimer">
                    <TimerDisplay
                        :remaining="remainingSeconds"
                        :minutes="minutes"
                        :seconds="seconds"
                        :is-warning="isWarning"
                        :is-danger="isDanger"
                        :theme="softHeader ? 'light' : 'dark'"
                    />
                    <div
                        class="h-6 w-px"
                        :class="softHeader ? 'bg-outline-variant/60' : 'bg-on-primary-container/20'"
                    ></div>
                </template>
                <span
                    class="truncate font-body-md text-body-md font-bold"
                    :class="softHeader ? 'text-text-heading' : 'text-on-primary-container'"
                >{{ title }}</span
                >
            </div>
            <div class="ml-auto flex items-center gap-3">
                <div
                    class="flex items-center gap-2 text-label-md"
                    :class="softHeader ? 'text-text-muted' : 'text-on-primary-container/80'"
                >
                    <span
                        class="w-2 h-2 rounded-full bg-success inline-block animate-pulse"
                    ></span>
                    <span>{{ sesiUjian.pengguna?.name || 'Peserta' }}</span>
                </div>
                <button
                    v-if="showThemeToggle"
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                    :class="softHeader
                        ? 'text-text-muted hover:bg-surface-container-high hover:text-text-heading'
                        : 'text-on-primary-container/80 hover:bg-on-primary-container/10'"
                    :aria-label="themeMode === 'dark' ? 'Gunakan mode terang' : 'Gunakan mode gelap'"
                    :title="themeMode === 'dark' ? 'Mode terang' : 'Mode gelap'"
                    @click="$emit('toggle-theme')"
                >
                    <IconSun v-if="themeMode === 'dark'" :size="19" />
                    <IconMoon v-else :size="19" />
                </button>
            </div>
        </header>
        <main class="pt-16 h-screen overflow-hidden flex flex-col">
            <div class="flex-1 overflow-hidden flex">
                <div class="flex-1 overflow-hidden">
                    <slot />
                </div>
                <aside
                    v-if="$slots.sidebar"
                    class="w-32 bg-surface-white border-l border-outline-variant flex flex-col items-center py-5 px-3 gap-3"
                >
                    <slot name="sidebar" />
                </aside>
            </div>
            <slot name="footer" />
        </main>
        <ConfirmDialog />
    </div>
</template>
