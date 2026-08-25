<script setup>
import { IconCheck, IconGridDots } from '@tabler/icons-vue';

defineProps({
    total: { type: Number, default: 0 },
    currentIndex: { type: Number, default: 0 },
    answers: { type: Array, default: () => [] },
});

const emit = defineEmits(['navigate', 'showGrid']);
</script>

<template>
    <div class="flex flex-col items-center gap-2 w-full">
        <div class="flex flex-col gap-1.5 w-full items-center">
            <div v-for="i in total" :key="i"
                 @click="emit('navigate', i - 1)"
                 class="w-10 h-10 rounded-xl flex items-center justify-center cursor-pointer text-label-md font-medium transition-all duration-200"
                 :class="[
                     currentIndex === i - 1
                         ? 'bg-primary-container text-white shadow-md scale-110'
                         : answers.includes(i - 1)
                             ? 'bg-green-100 text-green-700 border border-green-300'
                             : 'bg-surface-container-low text-text-body hover:bg-surface-container-highest hover:scale-105'
                 ]">
                 <IconCheck v-if="answers.includes(i - 1)" :size="16" />
                 <span v-else class="text-[12px] font-bold">{{ i }}</span>
            </div>
        </div>
        <button
            v-if="total > 0"
            @click="emit('showGrid')"
            class="mt-2 w-10 h-10 rounded-xl flex items-center justify-center text-text-muted hover:text-secondary hover:bg-pastel-purple/30 transition-all"
            title="Lihat Semua Soal"
        >
            <IconGridDots :size="18" />
        </button>
    </div>
</template>
