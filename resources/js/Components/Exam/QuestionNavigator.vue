<script setup>
import { IconCheck, IconFlag, IconQuestionMark } from '@tabler/icons-vue';

defineProps({
    total: { type: Number, default: 0 },
    currentIndex: { type: Number, default: 0 },
    answers: { type: Array, default: () => [] },
    flagged: { type: Array, default: () => [] },
});

const emit = defineEmits(['navigate']);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div v-for="i in total" :key="i"
             @click="emit('navigate', i - 1)"
             class="w-10 h-10 rounded-xl flex items-center justify-center cursor-pointer text-label-md font-medium transition-all"
             :class="currentIndex === i - 1 ? 'bg-primary-container text-white' : flagged.includes(i - 1) ? 'bg-amber-100 text-amber-700 border border-amber-300' : 'bg-surface-container-low text-text-body hover:bg-surface-container-highest'">
            <IconCheck v-if="answers.includes(i - 1)" :size="16" />
            <IconFlag v-else-if="flagged.includes(i - 1)" :size="16" />
            <IconQuestionMark v-else :size="16" />
        </div>
    </div>
</template>
