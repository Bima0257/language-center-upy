<script setup>
defineProps({
    remaining: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    isWarning: { type: Boolean, default: false },
    isDanger: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center gap-2 font-mono text-title-lg font-bold"
         :class="isDanger ? 'text-error-red' : isWarning ? 'text-amber-500' : 'text-primary'">
        <span class="tabular-nums">{{ String(minutes).padStart(2, '0') }}:{{ String(seconds).padStart(2, '0') }}</span>
    </div>
</template>
