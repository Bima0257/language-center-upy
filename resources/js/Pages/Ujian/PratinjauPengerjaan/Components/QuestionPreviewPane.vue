<script setup>
const props = defineProps({
    currentQuestion: { type: Object, default: null },
    globalNumber: { type: Number, default: 0 },
    totalAllQuestions: { type: Number, default: 0 },
    answeredCount: { type: Number, default: 0 },
    answers: { type: Object, default: () => ({}) },
    isAudio: { type: Boolean, default: false },
    showQuestionText: { type: Boolean, default: true },
});

const optionKeys = ["A", "B", "C", "D"];
const emit = defineEmits(["select-answer"]);

function optionText(key) {
    return props.currentQuestion
        ? props.currentQuestion["pilihan_" + key.toLowerCase()] || ""
        : "";
}

function selectAnswer(key) {
    emit("select-answer", key);
}
</script>

<template>
    <div class="flex-1 overflow-y-auto p-8 scroll-hide">
        <div class="max-w-lg mx-auto">
            <div class="flex items-center gap-2 mb-8">
                <div
                    class="w-8 h-8 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center font-bold"
                >
                    {{ globalNumber }}
                </div>
                <span
                    class="text-text-muted font-label-md text-label-md uppercase tracking-wider"
                    >SOAL {{ globalNumber }} DARI {{ totalAllQuestions }}</span
                >
                <span class="ml-auto shrink-0 text-label-md text-text-muted"
                    >Terjawab {{ answeredCount }}/{{ totalAllQuestions }}</span
                >
            </div>
            <h2
                v-if="showQuestionText && !isAudio"
                class="font-headline-md text-headline-md text-text-heading mb-10 leading-snug"
            >
                {{ currentQuestion?.teks_soal }}
            </h2>
            <h2
                v-else-if="showQuestionText && currentQuestion?.teks_soal"
                class="font-headline-md text-headline-md text-text-heading mb-6 leading-snug"
            >
                {{ currentQuestion.teks_soal }}
            </h2>
            <div class="space-y-2.5" v-if="currentQuestion">
                <button
                    v-for="key in optionKeys"
                    :key="key"
                    @click="selectAnswer(key)"
                    class="option-card w-full flex items-start gap-3 rounded-xl border bg-surface-white px-4 py-3 text-left transition-all duration-200 group cursor-pointer"
                    :class="
                        answers[currentQuestion.id] === key
                            ? 'option-selected'
                            : ''
                    "
                >
                    <div
                        class="option-key w-7 h-7 rounded-full border flex-shrink-0 flex items-center justify-center font-semibold text-[13px] transition-colors"
                        :class="
                            answers[currentQuestion.id] === key
                                ? 'option-key-selected bg-secondary-container text-on-secondary-container'
                                : 'text-text-muted group-hover:border-secondary group-hover:text-primary'
                        "
                    >
                        {{ key }}
                    </div>
                    <span
                        v-if="optionText(key)"
                        class="font-body-md text-[15px] text-text-body leading-relaxed"
                    >
                        {{ optionText(key) }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scroll-hide::-webkit-scrollbar {
    width: 6px;
}
.scroll-hide::-webkit-scrollbar-track {
    background: var(--color-scrollbar-track);
}
.scroll-hide::-webkit-scrollbar-thumb {
    background: var(--color-scrollbar-thumb);
    border-radius: 10px;
}
.option-card {
    border-color: var(--color-outline-variant) !important;
}
.dark .option-card {
    border-color: #64748b !important;
}
.dark .option-card:not(.option-selected) {
    border-color: #94a3b8 !important;
    background-color: var(--color-surface-container) !important;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.3);
}
.option-card:hover {
    border-color: var(--color-secondary) !important;
    background-color: var(--color-surface-container-low);
}
.dark .option-card:not(.option-selected):hover {
    border-color: var(--color-secondary) !important;
    background-color: var(--color-surface-container-high) !important;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-secondary) 35%, transparent);
}
.option-key {
    border-color: var(--color-outline-variant) !important;
}
.dark .option-key {
    border-color: #64748b !important;
}
.option-key-selected {
    border-color: var(--color-secondary) !important;
}
.option-selected {
    border-color: var(--color-secondary) !important;
    background-color: var(--color-pastel-purple) !important;
}
</style>
