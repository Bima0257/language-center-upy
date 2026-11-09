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
    largeHeader: { type: Boolean, default: false },
    compactHeader: { type: Boolean, default: false },
    surfaceHeader: { type: Boolean, default: false },
    primaryHeader: { type: Boolean, default: false },
    darkHeader: { type: Boolean, default: false },
});

defineEmits(['toggle-theme']);
</script>

<template>
    <div class="min-h-screen bg-canvas">
        <header
            class="fixed inset-x-0 top-0 z-50 flex items-center"
            :class="[
                compactHeader
                    ? 'h-14 gap-3 px-4 sm:px-5'
                    : largeHeader
                        ? 'h-20 gap-5 px-6'
                        : 'h-16 gap-5 px-6',
                surfaceHeader
                    ? 'exam-header-surface'
                    : primaryHeader
                        ? 'exam-header-primary'
                    : darkHeader
                        ? 'exam-header-dark'
                        : softHeader
                            ? 'border-b border-outline-variant/60 bg-surface-container-low shadow-sm'
                            : 'bg-primary-container',
            ]"
        >
            <slot name="header">
                <div class="flex items-center gap-3 min-w-0">
                    <template v-if="showTimer">
                        <TimerDisplay
                            :remaining="remainingSeconds"
                            :minutes="minutes"
                            :seconds="seconds"
                            :is-warning="isWarning"
                            :is-danger="isDanger"
                            :theme="softHeader || surfaceHeader ? 'light' : 'dark'"
                        />
                        <div
                            class="h-6 w-px"
                            :class="softHeader || surfaceHeader ? 'bg-outline-variant/60' : 'bg-on-primary/20'"
                        ></div>
                    </template>
                    <span
                        class="truncate font-body-md text-body-md font-bold"
                        :class="softHeader || surfaceHeader ? 'text-text-heading' : 'text-on-primary'"
                    >{{ title }}</span
                    >
                </div>
                <div class="ml-auto flex items-center gap-3">
                    <div
                        class="flex items-center gap-2 text-label-md"
                        :class="softHeader || surfaceHeader ? 'text-text-muted' : 'text-on-primary/80'"
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
                        :class="softHeader || surfaceHeader
                            ? 'text-text-muted hover:bg-surface-container-high hover:text-text-heading'
                            : 'text-on-primary/80 hover:bg-on-primary/10'"
                        :aria-label="themeMode === 'dark' ? 'Gunakan mode terang' : 'Gunakan mode gelap'"
                        :title="themeMode === 'dark' ? 'Mode terang' : 'Mode gelap'"
                        @click="$emit('toggle-theme')"
                    >
                        <IconSun v-if="themeMode === 'dark'" :size="19" />
                        <IconMoon v-else :size="19" />
                    </button>
                </div>
            </slot>
        </header>
        <main
            class="h-screen overflow-hidden flex flex-col"
            :class="compactHeader ? 'pt-14' : largeHeader ? 'pt-20' : 'pt-16'"
        >
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

<style scoped>
.exam-header-surface {
    border-bottom: 1px solid color-mix(
        in srgb,
        var(--color-outline-variant) 72%,
        transparent
    );
    background-color: var(--color-surface-white);
}

.exam-header-primary {
    border-bottom: 1px solid color-mix(
        in srgb,
        var(--color-on-primary) 18%,
        transparent
    );
    background-color: var(--color-primary);
}

.exam-header-dark {
    border-bottom: 1px solid color-mix(
        in srgb,
        var(--color-outline-variant) 58%,
        transparent
    );
    background-color: var(--color-primary-container);
}
</style>
