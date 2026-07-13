<script setup>
defineProps({
    answeredCount: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    remainingSeconds: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    isWarning: { type: Boolean, default: false },
    isDanger: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center gap-4 text-label-md">
        <span class="text-text-muted">{{ answeredCount }}/{{ total }} terjawab</span>
        <span class="w-px h-4 bg-outline-variant"></span>
        <span class="font-mono font-bold" :class="isDanger ? 'text-error-red' : isWarning ? 'text-amber-500' : 'text-primary'">
            {{ String(minutes).padStart(2, '0') }}:{{ String(seconds).padStart(2, '0') }}
        </span>
    </div>
</template>
