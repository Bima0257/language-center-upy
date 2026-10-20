<script setup>
import { IconArrowLeft, IconArrowRight, IconMap } from '@tabler/icons-vue';

defineProps({
    currentQuestionIndex: { type: Number, default: 0 },
    jumlahSoal: { type: Number, default: 0 },
    currentQuestions: { type: Array, default: () => [] },
    answers: { type: Object, default: () => ({}) },
    visibleNumbers: { type: Array, default: () => [] },
    stripAnim: { type: String, default: 'strip-none' },
});

const emit = defineEmits(['navigate', 'submit', 'show-grid']);

function onBeforeLeaveStrip(el) {
    el.style.left = `${el.offsetLeft}px`;
    el.style.top = `${el.offsetTop}px`;
}
</script>

<template>
    <div
        class="shrink-0 border-t border-outline-variant bg-surface-container-low/60 px-4 py-3.5 min-h-[68px] flex items-center gap-2"
    >
        <button
            @click="emit('navigate', currentQuestionIndex - 1)"
            :disabled="currentQuestionIndex === 0"
            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-40 disabled:cursor-not-allowed"
        >
            <IconArrowLeft :size="14" /> Prev
        </button>

        <TransitionGroup
            :name="stripAnim"
            tag="div"
            class="relative flex items-center gap-1 min-h-[28px]"
            @before-leave="onBeforeLeaveStrip"
        >
            <button
                v-for="i in visibleNumbers"
                :key="currentQuestions[i].id"
                @click="emit('navigate', i)"
                class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-[12px] font-bold transition-all duration-150 cursor-pointer"
                :class="[
                    i === currentQuestionIndex
                        ? 'bg-secondary text-on-secondary ring-2 ring-secondary/30 scale-105 shadow-sm'
                        : answers[currentQuestions[i].id] !== undefined
                            ? 'bg-secondary/10 text-secondary border border-secondary/40 hover:bg-secondary/20'
                            : 'bg-surface-white text-text-muted border border-outline-variant/60 hover:bg-surface-container-highest'
                ]"
            >
                {{ i + 1 }}
            </button>
        </TransitionGroup>

        <button
            v-if="currentQuestionIndex < jumlahSoal - 1"
            @click="emit('navigate', currentQuestionIndex + 1)"
            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary transition-all active:scale-95 duration-150"
        >
            Next <IconArrowRight :size="14" />
        </button>
        <button
            v-else
            @click="emit('submit')"
            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold bg-secondary text-on-secondary hover:bg-secondary/90 transition-all active:scale-95 duration-150"
        >
            Kumpulkan
        </button>

        <button
            @click="emit('show-grid')"
            class="ml-auto shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
        >
            <IconMap :size="14" /> Peta Soal
        </button>
    </div>
</template>

<style scoped>
.strip-next-move,
.strip-prev-move {
    transition: transform 0.25s ease;
}
.strip-next-enter-active {
    transition: all 0.22s ease-out;
}
.strip-next-leave-active {
    transition: all 0.18s ease-in;
    position: absolute;
}
.strip-next-enter-from {
    opacity: 0;
    transform: translateX(16px) scale(0.85);
}
.strip-next-leave-to {
    opacity: 0;
    transform: translateX(-16px) scale(0.85);
}
.strip-prev-enter-active {
    transition: all 0.22s ease-out;
}
.strip-prev-leave-active {
    transition: all 0.18s ease-in;
    position: absolute;
}
.strip-prev-enter-from {
    opacity: 0;
    transform: translateX(-16px) scale(0.85);
}
.strip-prev-leave-to {
    opacity: 0;
    transform: translateX(16px) scale(0.85);
}
</style>
