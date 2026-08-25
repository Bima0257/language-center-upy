<script setup>
import { IconClock } from '@tabler/icons-vue';

defineProps({
    remaining: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    isWarning: { type: Boolean, default: false },
    isDanger: { type: Boolean, default: false },
    theme: { type: String, default: 'light' },
});
</script>

<template>
    <div class="flex items-center gap-2"
         :class="[
             theme === 'dark'
                 ? (isDanger ? 'text-red-300' : isWarning ? 'text-amber-300' : 'text-white')
                 : (isDanger ? 'text-error-red' : isWarning ? 'text-amber-500 dark:text-amber-400' : 'text-primary'),
         ]">
        <IconClock :size="18" class="opacity-80" />
        <span class="font-mono text-title-lg font-bold tabular-nums tracking-wider">
            {{ String(minutes).padStart(2, '0') }}:{{ String(seconds).padStart(2, '0') }}
        </span>
    </div>
</template>
