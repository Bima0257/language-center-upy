<script setup>
import { ref } from 'vue';
import KartuSoal from './KartuSoal.vue';

defineProps({
    questions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    materiSoalMode: { type: String, default: 'none' },
    optionKeys: { type: Array, default: () => ['A', 'B', 'C', 'D'] },
    isAudioQuestion: { type: Function, required: true },
});

const emit = defineEmits(['add', 'remove']);
const questionRefs = ref([]);

function setQuestionRef(questionCard, index) {
    questionRefs.value[index] = questionCard?.$el ?? questionCard;
}

function scrollTo(index) {
    questionRefs.value[index]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

defineExpose({ scrollTo });
</script>

<template>
    <div class="space-y-4">
        <KartuSoal
            v-for="(question, questionIndex) in questions"
            :key="questionIndex"
            :ref="card => setQuestionRef(card, questionIndex)"
            :question="question"
            :question-index="questionIndex"
            :question-count="questions.length"
            :show-add="questionIndex === questions.length - 1"
            :errors="errors"
            :is-audio="isAudioQuestion(question)"
            :materi-soal-mode="materiSoalMode"
            :option-keys="optionKeys"
            @add="emit('add', questionIndex)"
            @remove="emit('remove', questionIndex)"
        />
    </div>
</template>
