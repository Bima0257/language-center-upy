<script setup>
import { computed } from "vue";

const props = defineProps({
    currentQuestion: { type: Object, default: null },
    questionNumber: { type: Number, default: 0 },
    globalNumber: { type: Number, default: 0 },
    totalAllQuestions: { type: Number, default: 0 },
    answeredCount: { type: Number, default: 0 },
    answers: { type: Object, default: () => ({}) },
    isAudio: { type: Boolean, default: false },
    showQuestionText: { type: Boolean, default: true },
});

const emit = defineEmits(["select-answer"]);

function optionText(key) {
    return props.currentQuestion
        ? props.currentQuestion["pilihan_" + key.toLowerCase()] || ""
        : "";
}

function selectAnswer(key) {
    emit("select-answer", key);
}

const optionKeys = computed(() =>
    ["A", "B", "C", "D"].filter((key) => optionText(key)),
);
</script>

<template>
    <div class="min-h-0 flex-1 overflow-y-auto scroll-hide p-2.5 sm:p-3 lg:p-4">
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-2 flex items-center gap-1.5">
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-on-primary"
                >
                    {{ questionNumber || globalNumber }}
                </div>
                <span class="text-[11px] font-semibold text-text-body sm:text-xs"
                    >Soal {{ questionNumber || globalNumber }} dari
                    {{ totalAllQuestions }}</span
                >
                <span class="ml-auto shrink-0 text-[11px] text-text-body sm:text-xs"
                    >Terjawab {{ answeredCount }}/{{ totalAllQuestions }}</span
                >
            </div>
            <h2
                v-if="showQuestionText && currentQuestion?.teks_soal"
                class="mb-3 text-base font-semibold leading-[1.35] tracking-[-0.02em] text-text-heading sm:text-lg"
            >
                {{ currentQuestion.teks_soal }}
            </h2>
            <div v-if="currentQuestion" class="space-y-1.5">
                <button
                    v-for="key in optionKeys"
                    :key="key"
                    type="button"
                    :aria-label="`Pilih jawaban ${key}`"
                    :aria-pressed="answers[currentQuestion.id] === key"
                    @click="selectAnswer(key)"
                    class="option-card group flex min-h-[44px] w-full cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-left transition-all duration-200 motion-reduce:transition-none sm:min-h-[48px] sm:gap-2 sm:px-3"
                    :class="
                        answers[currentQuestion.id] === key
                            ? 'option-selected'
                            : ''
                    "
                >
                    <div
                        class="option-key flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold transition-colors sm:h-8 sm:w-8"
                        :class="
                            answers[currentQuestion.id] === key
                                ? 'option-key-selected bg-primary text-on-primary'
                                : 'text-text-muted group-hover:border-secondary group-hover:text-primary'
                        "
                    >
                        {{ key }}
                    </div>
                    <span
                        class="font-body-md text-[13px] leading-[1.35] text-text-body sm:text-sm"
                    >
                        {{ optionText(key) }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.option-card {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 92%,
        var(--color-text-muted)
    );
    background-color: var(--color-surface-white);
}

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
.option-card:hover {
    border-color: var(--color-secondary);
    background-color: color-mix(
        in srgb,
        var(--color-surface-container-high) 78%,
        transparent
    );
}

.option-card:focus-visible {
    outline: 2px solid var(--color-secondary);
    outline-offset: 3px;
}

.option-key {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 82%,
        var(--color-text-muted)
    );
}

.option-key-selected {
    border-color: var(--color-primary);
}

.option-selected {
    border-color: var(--color-primary);
    background-color: color-mix(
        in srgb,
        var(--color-primary) 22%,
        var(--color-surface-container)
    );
}
</style>
