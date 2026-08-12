<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import ExamLayout from '@/Layouts/ExamLayout.vue';
import PassageViewer from '@/Components/Exam/PassageViewer.vue';
import AudioPlayer from '@/Components/Exam/AudioPlayer.vue';
import QuestionNavigator from '@/Components/Exam/QuestionNavigator.vue';
import ViolationModal from '@/Components/Exam/ViolationModal.vue';
import { useMediaLoad } from '@/Composables/useMediaLoad';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useExamTimer } from '@/Modules/ExamModule/Composables/useExamTimer.js';
import { useExamSecurity } from '@/Modules/ExamModule/Composables/useExamSecurity.js';
import { useAutoSave } from '@/Modules/ExamModule/Composables/useAutoSave.js';
import axios from 'axios';

const props = defineProps({
    session: { type: Object, required: true },
    bankQuestions: { type: Array, default: () => [] },
    skills: { type: Object, default: () => ({}) },
});

const user = usePage().props.auth.user;
const currentSection = computed(() => props.session.current_section);

const currentQuestions = computed(() => {
    const skillId = currentSection.value?.skill_id;
    if (!skillId) return [];
    return props.bankQuestions
        .filter(q => q.skill_id === skillId)
        .sort((a, b) => (a.order || 0) - (b.order || 0));
});

const totalQuestions = computed(() => currentQuestions.value.length);

const optionKeys = ['A', 'B', 'C', 'D'];

function qOptions(q) {
    return optionKeys
        .map(k => ({ key: k, text: q['option_' + k.toLowerCase()] }))
        .filter(o => o.text);
}

const answers = ref({});

const sessionAnswers = props.session.answers || [];

for (const a of sessionAnswers) {
    answers.value[a.question_id] = a.answer_text;
}

const answeredIds = computed(() => Object.keys(answers.value).map(Number));
const answeredIndices = computed(() => {
    return currentQuestions.value
        .map((q, i) => answers.value[q.id] !== undefined ? i : -1)
        .filter(i => i >= 0);
});

const elapsedSeconds = props.session.started_at
    ? Math.floor((Date.now() - new Date(props.session.started_at).getTime()) / 1000)
    : 0;

const totalSeconds = computed(() => (props.session.schedule?.exam?.duration_minutes || 35) * 60);

const { remaining, minutes, seconds, isWarning, isDanger, start: startTimer } = useExamTimer({
    totalSeconds: totalSeconds.value,
    elapsedSeconds,
    onExpire: () => handleTimeUp(),
});

const {
    violations,
    strikeCount,
    activate: activateSecurity,
    deactivate: deactivateSecurity,
} = useExamSecurity({
    sessionId: props.session.id,
    heartbeatInterval: 30000,
});

const { lastSaved, isSaving, save: autoSave } = useAutoSave({
    sessionId: props.session.id,
    debounceMs: 500,
});

const currentQuestionIndex = ref(0);
const currentQuestion = computed(() => currentQuestions.value[currentQuestionIndex.value] || null);
const showViolationModal = ref(false);
const currentViolation = ref(null);
const showSubmitConfirm = ref(false);

const currentAudioSrc = computed(() => {
    const path = currentQuestion.value?.audio_url || currentQuestion.value?.passage?.audio_url;
    return path ? '/storage/' + path : null;
});

const currentImageSrc = computed(() => {
    const path = currentQuestion.value?.image_url || currentQuestion.value?.passage?.image_url;
    return path ? '/storage/' + path : null;
});

const currentImageLoading = useMediaLoad(currentImageSrc);

function goToQuestion(index) {
    if (index >= 0 && index < totalQuestions.value) {
        currentQuestionIndex.value = index;
    }
}

function selectAnswer(key) {
    const q = currentQuestion.value;
    if (!q) return;
    answers.value[q.id] = key;
    autoSave(q.id, key);
}

function isAnswered(qId) {
    return answers.value[qId] !== undefined;
}

function handleTimeUp() {
    axios.post(`/exam/session/${props.session.id}/section-complete`).catch(() => {});
}

function submitExam() {
    axios.post(`/exam/session/${props.session.id}/submit`)
        .then(() => { window.location.href = '/dashboard'; })
        .catch(() => {});
}

watch(() => violations.value.length, () => {
    if (violations.value.length > 0) {
        const last = violations.value[violations.value.length - 1];
        currentViolation.value = last;
        showViolationModal.value = true;
    }
});

watch(strikeCount, (val) => {
    if (val >= 3) {
        showViolationModal.value = false;
    }
});

function backToExam() {
    showViolationModal.value = false;
}

onMounted(() => {
    startTimer();
    activateSecurity();
});

onUnmounted(() => {
    deactivateSecurity();
});
</script>

<template>
    <Head title="Ujian" />
    <ExamLayout :session="session" :remaining-seconds="remaining" :minutes="minutes" :seconds="seconds"
                :is-warning="isWarning" :is-danger="isDanger">
        <div class="flex h-full">
            <div v-if="currentSection?.skill?.code === 'reading'" class="flex-1 flex">
                <div class="w-1/2 p-6 overflow-y-auto border-r border-outline-variant/30">
                    <PassageViewer v-if="currentQuestion?.passage" :passage="currentQuestion.passage" />
                </div>
                <div class="w-1/2 p-6 overflow-y-auto">
                    <div v-if="currentQuestion" class="mb-4">
                        <p class="text-body-md font-medium text-primary mb-1">
                            Soal {{ currentQuestionIndex + 1 }} dari {{ totalQuestions }}
                        </p>
                    </div>
                    <BaseCard v-if="currentQuestion">
                        <p class="text-body-md font-medium text-primary mb-4">{{ currentQuestion.question_text }}</p>
                        <div v-if="qOptions(currentQuestion).length" class="space-y-3">
                            <button v-for="opt in qOptions(currentQuestion)" :key="opt.key"
                                    @click="selectAnswer(opt.key)"
                                    class="w-full text-left p-4 rounded-2xl border transition-all"
                                    :class="answers[currentQuestion.id] === opt.key ? 'border-secondary bg-pastel-purple/20 text-primary' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary hover:bg-pastel-purple/10'">
                                <span class="font-semibold">{{ opt.key }}.</span> {{ opt.text }}
                            </button>
                        </div>
                    </BaseCard>
                    <div v-else class="text-center py-10">
                        <p class="text-text-body text-body-md">Tidak ada soal pada section ini.</p>
                    </div>
                    <div class="flex items-center justify-between mt-6 gap-3">
                        <div class="flex gap-2">
                            <button @click="goToQuestion(currentQuestionIndex - 1)" :disabled="currentQuestionIndex === 0"
                                    class="px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-30">
                                ← Sebelumnya
                            </button>
                            <button @click="goToQuestion(currentQuestionIndex + 1)" :disabled="currentQuestionIndex >= totalQuestions - 1"
                                    class="px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-30">
                                Selanjutnya →
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-label-md">
                        <span v-if="isSaving" class="text-text-muted">Menyimpan...</span>
                        <span v-else-if="lastSaved" class="text-green-600 dark:text-green-400">✓ Tersimpan {{ lastSaved }}</span>
                        <span v-else class="text-text-muted">Belum terjawab</span>
                    </div>
                </div>
            </div>

            <div v-else-if="currentSection?.skill?.code === 'listening'" class="flex-1 p-6 overflow-y-auto">
                <div class="mb-6">
                    <p class="text-label-md text-text-muted mb-2">Putar audio sebelum menjawab soal</p>
                    <div v-if="currentAudioSrc || currentImageSrc" class="bg-surface-container-low rounded-2xl border border-surface-variant overflow-hidden">
                        <div v-if="currentImageSrc" class="w-full bg-surface-container-lowest">
                            <BaseMediaLoader
                                :loading="currentImageLoading.loading.value"
                                media-type="image"
                                skeleton-class="h-64 max-h-[320px] rounded-none border-0"
                            >
                                <img
                                    :src="currentImageSrc"
                                    class="w-full h-auto max-h-[320px] object-contain"
                                    @load="currentImageLoading.onLoad()"
                                    @error="currentImageLoading.onError()"
                                />
                            </BaseMediaLoader>
                        </div>
                        <AudioPlayer v-if="currentAudioSrc" :src="currentAudioSrc" strip />
                    </div>
                    <div v-else class="bg-pastel-blue/20 rounded-2xl p-6 text-center border border-dashed border-outline-variant">
                        <p class="text-text-muted text-body-md">Audio akan tersedia di sini</p>
                    </div>
                </div>
                <BaseCard v-if="currentQuestion">
                    <p class="text-body-md font-medium text-primary mb-2">Soal {{ currentQuestionIndex + 1 }}</p>
                    <p v-if="currentQuestion.question_text" class="text-body-md text-primary mb-4">{{ currentQuestion.question_text }}</p>
                    <div class="space-y-3">
                        <button v-for="key in optionKeys" :key="key"
                                @click="selectAnswer(key)"
                                class="w-full text-left p-4 rounded-2xl border transition-all"
                                :class="answers[currentQuestion.id] === key ? 'border-secondary bg-pastel-purple/20 text-primary' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary'">
                            <span class="font-semibold">{{ key }}.</span>
                            <span v-if="currentQuestion['option_' + key.toLowerCase()]">{{ currentQuestion['option_' + key.toLowerCase()] }}</span>
                        </button>
                    </div>
                </BaseCard>
                <div class="flex items-center justify-between mt-6 gap-3">
                    <button @click="goToQuestion(currentQuestionIndex - 1)" :disabled="currentQuestionIndex === 0"
                            class="px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-30">
                        ← Sebelumnya
                    </button>
                    <button @click="goToQuestion(currentQuestionIndex + 1)" :disabled="currentQuestionIndex >= totalQuestions - 1"
                            class="px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-30">
                        Selanjutnya →
                    </button>
                </div>
            </div>

            <div v-else class="flex-1 flex items-center justify-center p-6">
                <p class="text-text-body text-body-md">Section belum tersedia.</p>
            </div>
        </div>

        <template #sidebar>
            <div class="flex flex-col items-center gap-2">
                <QuestionNavigator
                    :total="totalQuestions"
                    :current-index="currentQuestionIndex"
                    :answers="answeredIndices"
                    @navigate="goToQuestion" />
            </div>
        </template>

        <template #footer>
            <div class="fixed bottom-0 inset-x-0 h-16 bg-surface-white border-t border-outline-variant flex items-center px-6 gap-4">
                <div class="flex-1 flex items-center gap-4">
                    <span class="text-label-md text-text-muted">
                        Terjawab {{ answeredIds.length }}/{{ totalQuestions }}
                    </span>
                    <span v-if="strikeCount > 0" class="text-label-md"
                          :class="strikeCount >= 3 ? 'text-error-red font-bold' : 'text-amber-600 dark:text-amber-400'">
                        ⚠ {{ strikeCount }}/3 pelanggaran
                    </span>
                </div>
                <button @click="showSubmitConfirm = true"
                        class="bg-primary-container text-white px-8 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                    Kumpulkan Ujian
                </button>
            </div>
        </template>
    </ExamLayout>

    <div v-if="showSubmitConfirm" class="fixed inset-0 z-[200] bg-black/50 flex items-center justify-center p-6"
         @click.self="showSubmitConfirm = false">
        <div class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center">
            <h2 class="text-headline-md font-bold text-primary mb-2">Yakin ingin mengumpulkan?</h2>
            <p class="text-text-body text-body-md mb-2">
                Soal terjawab: {{ answeredIds.length }} dari {{ totalQuestions }}
            </p>
            <p class="text-text-muted text-label-md mb-6">Jawaban tidak bisa diubah setelah dikumpulkan.</p>
            <div class="flex gap-4">
                <BaseButton variant="secondary" size="lg" class="flex-1" @click="showSubmitConfirm = false">
                    Kembali
                </BaseButton>
                <BaseButton size="lg" class="flex-1" @click="submitExam">
                    Kumpulkan
                </BaseButton>
            </div>
        </div>
    </div>

    <ViolationModal :show="showViolationModal" :message="currentViolation?.type?.replace(/_/g, ' ') || ''"
                    :strike="strikeCount" @back="backToExam" @close="backToExam" />
</template>
