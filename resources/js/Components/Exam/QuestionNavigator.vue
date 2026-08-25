<script setup>
import { IconGridDots } from '@tabler/icons-vue';

defineProps({
    total: { type: Number, default: 0 },
    currentIndex: { type: Number, default: 0 },
    answers: { type: Array, default: () => [] },
});

const emit = defineEmits(['navigate', 'showGrid']);
</script>

<template>
    <div class="flex flex-col gap-3 w-full">
        <div class="w-full rounded-xl bg-surface-container-low/70 px-2.5 py-2 text-center">
            <p class="text-label-md text-text-muted uppercase tracking-wider">Soal</p>
            <p class="text-label-md font-bold text-text-heading">
                Terjawab {{ answers.length }}/{{ total }}
            </p>
        </div>

        <div class="grid grid-cols-2 gap-2 w-full">
            <button v-for="i in total" :key="i"
                 @click="emit('navigate', i - 1)"
                 class="h-11 w-full rounded-lg flex items-center justify-center cursor-pointer text-[13px] font-bold transition-all duration-150"
                 :class="[
                     currentIndex === i - 1
                         ? 'bg-secondary-container text-white ring-2 ring-secondary/30 scale-[1.04] shadow-sm'
                         : answers.includes(i - 1)
                             ? 'bg-secondary/10 text-secondary border border-secondary/40 hover:bg-secondary/20'
                             : 'bg-surface-container-low text-text-muted border border-outline-variant/50 hover:bg-surface-container-highest'
                 ]">
                 {{ i }}
            </button>
        </div>

        <button
            v-if="total > 0"
            @click="emit('showGrid')"
            class="mt-1 w-full flex items-center justify-center gap-1.5 px-2 py-2 rounded-full text-[12px] font-semibold border border-outline-variant text-text-body hover:bg-surface-container-low transition-all"
            title="Lihat Semua Soal"
        >
            <IconGridDots :size="14" />
            Semua Soal
        </button>
    </div>
</template>
