<script setup>
import { Head } from "@inertiajs/vue3";
import ExamLayout from "@/Layouts/ExamLayout.vue";
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import AudioPlayer from "@/Components/Exam/AudioPlayer.vue";
import QuestionNavigator from "@/Components/Exam/QuestionNavigator.vue";
import ViolationModal from "@/Components/Exam/ViolationModal.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useExamTimer } from "@/Modules/ExamModule/Composables/useExamTimer.js";
import { useExamSecurity } from "@/Modules/ExamModule/Composables/useExamSecurity.js";
import { useAutoSave } from "@/Modules/ExamModule/Composables/useAutoSave.js";
import { materialOfSkill } from "@/constants/skills";
import { IconArrowLeft, IconArrowRight } from "@tabler/icons-vue";
import axios from "axios";

const props = defineProps({
    session: { type: Object, required: true },
    bankQuestions: { type: Array, default: () => [] },
});

const currentSection = computed(() => props.session.current_section);

const currentQuestions = computed(() => {
    const skillId = currentSection.value?.skill_id;
    if (!skillId) return props.bankQuestions;
    return props.bankQuestions
        .filter((q) => q.skill_id === skillId)
        .sort((a, b) => (a.order || 0) - (b.order || 0));
});

const totalQuestions = computed(() => currentQuestions.value.length);

const optionKeys = ["A", "B", "C", "D"];

const answers = ref({});

const sessionAnswers = props.session.answers || [];

for (const a of sessionAnswers) {
    answers.value[a.question_id] = a.answer_text;
}

const answeredIds = computed(() => Object.keys(answers.value).map(Number));
const answeredIndices = computed(() => {
    return currentQuestions.value
        .map((q, i) => (answers.value[q.id] !== undefined ? i : -1))
        .filter((i) => i >= 0);
});

const elapsedSeconds = props.session.started_at
    ? Math.floor(
          (Date.now() - new Date(props.session.started_at).getTime()) / 1000,
      )
    : 0;

const totalSeconds = computed(
    () => (props.session.slot?.schedule?.exam?.duration_minutes || 35) * 60,
);

const {
    remaining,
    minutes,
    seconds,
    isWarning,
    isDanger,
    start: startTimer,
} = useExamTimer({
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
const currentQuestion = computed(
    () => currentQuestions.value[currentQuestionIndex.value] || null,
);
const showViolationModal = ref(false);
const currentViolation = ref(null);
const showSubmitConfirm = ref(false);

const currentSkillCode = computed(
    () => currentQuestion.value?.skill?.code || "",
);
const isMaterialAudio = computed(
    () => materialOfSkill(currentSkillCode.value) === "audio",
);
const passage = computed(() => currentQuestion.value?.passage || null);
const partLabel = computed(
    () =>
        currentQuestion.value?.skillPart?.name ||
        currentSkillCode.value ||
        "",
);

const audioSrc = computed(() => {
    const path = passage.value?.audio_url;
    return path ? "/media/" + path : null;
});

const imageSrc = computed(() => {
    const path = passage.value?.image_url;
    return path ? "/media/" + path : null;
});

const imageLoading = useMediaLoad(imageSrc);

function optionText(key) {
    return currentQuestion.value
        ? currentQuestion.value["option_" + key.toLowerCase()] || ""
        : "";
}

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

function handleTimeUp() {
    axios
        .post(`/exam/session/${props.session.id}/section-complete`)
        .catch(() => {});
}

function submitExam() {
    axios
        .post(`/exam/session/${props.session.id}/submit`)
        .then(() => {
            window.location.href = "/dashboard";
        })
        .catch(() => {});
}

watch(
    () => violations.value.length,
    () => {
        if (violations.value.length > 0) {
            const last = violations.value[violations.value.length - 1];
            currentViolation.value = last;
            showViolationModal.value = true;
        }
    },
);

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
    <ExamLayout
        :session="session"
        :remaining-seconds="remaining"
        :minutes="minutes"
        :seconds="seconds"
        :is-warning="isWarning"
        :is-danger="isDanger"
    >
        <div class="flex h-full">
            <!-- READING: SPLIT PANE -->
            <template v-if="!isMaterialAudio">
                <!-- LEFT PANE: PASSAGE -->
                <section
                    class="w-1/2 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright"
                >
                    <div class="max-w-xl mx-auto">
                        <div class="mb-8">
                            <span
                                class="bg-pastel-blue text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block"
                                >{{ partLabel || "Materi" }}</span
                            >
                            <h1
                                v-if="passage"
                                class="font-serif text-2xl leading-tight text-primary font-bold mb-6"
                            >
                                {{ passage.title }}
                            </h1>
                            <h1
                                v-else
                                class="font-serif text-2xl leading-tight text-primary font-bold mb-6"
                            >
                                Soal Standalone
                            </h1>
                        </div>
                        <div
                            v-if="passage?.content_text"
                            class="font-serif text-[18px] leading-[1.8] text-on-background space-y-6 [&_.rich-text-content]:leading-[1.8] [&_.rich-text-content]:text-on-background [&_.rich-text-content_p]:mb-6"
                        >
                            <RichTextViewer :content="passage.content_text" />
                        </div>
                        <div
                            v-if="passage?.image_url"
                            class="my-6 rounded-xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard"
                        >
                            <BaseMediaLoader
                                :loading="imageLoading.loading.value"
                                media-type="image"
                                skeleton-class="h-44 rounded-lg"
                            >
                                <img
                                    :src="'/media/' + passage.image_url"
                                    class="w-full h-auto rounded-lg"
                                    @load="imageLoading.onLoad()"
                                    @error="imageLoading.onError()"
                                />
                            </BaseMediaLoader>
                        </div>
                    </div>
                </section>
                <!-- RIGHT PANE: QUESTION -->
                <section class="w-1/2 flex flex-col bg-surface-white">
                    <div class="flex-1 overflow-y-auto p-8 scroll-hide">
                        <div class="max-w-lg mx-auto">
                            <div class="flex items-center gap-2 mb-8">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary-container text-white flex items-center justify-center font-bold"
                                >
                                    {{ currentQuestionIndex + 1 }}
                                </div>
                                <span
                                    class="text-text-muted font-label-md text-label-md uppercase tracking-wider"
                                    >QUESTION {{ currentQuestionIndex + 1 }} OF
                                    {{ totalQuestions }}</span
                                >
                            </div>
                            <h2
                                class="font-headline-md text-headline-md text-text-heading mb-10 leading-snug"
                            >
                                {{ currentQuestion?.question_text }}
                            </h2>
                            <div
                                class="space-y-4"
                                v-if="currentQuestion"
                            >
                                <button
                                    v-for="key in optionKeys"
                                    :key="key"
                                    @click="selectAnswer(key)"
                                    class="option-card w-full flex items-start gap-4 p-5 border-2 rounded-2xl text-left transition-all duration-200 group cursor-pointer"
                                    :class="
                                        answers[currentQuestion.id] === key
                                            ? 'option-selected'
                                            : 'border-surface-container-high hover:border-secondary hover:bg-pastel-purple/10'
                                    "
                                >
                                    <div
                                        class="w-8 h-8 rounded-full border-2 flex-shrink-0 flex items-center justify-center font-bold transition-colors"
                                        :class="
                                            answers[currentQuestion.id] === key
                                                ? 'bg-secondary-container border-secondary text-white'
                                                : 'border-outline-variant text-text-muted group-hover:border-secondary group-hover:text-primary'
                                        "
                                    >
                                        {{ key }}
                                    </div>
                                    <span
                                        v-if="optionText(key)"
                                        class="font-body-md text-title-lg text-on-surface-variant leading-tight pt-1"
                                    >
                                        {{ optionText(key) }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </template>

            <!-- AUDIO + GAMBAR: SPLIT PANE -->
            <template v-else>
                <!-- LEFT PANE: MEDIA -->
                <section
                    class="w-1/2 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright"
                >
                    <div class="max-w-xl mx-auto">
                        <div class="mb-8">
                            <span
                                class="bg-pastel-purple text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block"
                                >{{ partLabel || "Audio" }}</span
                            >
                            <h1
                                v-if="passage"
                                class="font-serif text-2xl leading-tight text-primary font-bold mb-6"
                            >
                                {{ passage.title }}
                            </h1>
                            <h1
                                v-else
                                class="font-serif text-2xl leading-tight text-primary font-bold mb-6"
                            >
                                {{ partLabel || "Audio" }}
                            </h1>
                        </div>
                        <p class="text-label-md text-text-muted mb-4">
                            Putar audio sebelum menjawab soal
                        </p>
                        <div
                            class="bg-surface-container-low rounded-2xl border border-surface-variant overflow-hidden flex flex-col"
                        >
                            <div
                                v-if="imageSrc"
                                class="relative w-full min-h-[300px]"
                            >
                                <BaseMediaLoader
                                    :loading="imageLoading.loading.value"
                                    media-type="image"
                                    skeleton-class="h-full min-h-[300px] rounded-none border-0"
                                >
                                    <img
                                        :src="imageSrc"
                                        class="absolute inset-0 w-full h-full object-cover"
                                        @load="imageLoading.onLoad()"
                                        @error="imageLoading.onError()"
                                    />
                                </BaseMediaLoader>
                                <div class="absolute inset-0 bg-black/5"></div>
                            </div>
                            <AudioPlayer
                                v-if="audioSrc"
                                :src="audioSrc"
                                strip
                            />
                            <div
                                v-else
                                class="bg-surface-white p-6 text-center border-t border-surface-variant"
                            >
                                <p class="text-text-muted text-body-md">
                                    Belum ada audio untuk soal ini.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- RIGHT PANE: QUESTION -->
                <section class="w-1/2 flex flex-col bg-surface-white">
                    <div class="flex-1 overflow-y-auto p-8 scroll-hide">
                        <div class="max-w-lg mx-auto">
                            <div class="flex items-center gap-2 mb-8">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary-container text-white flex items-center justify-center font-bold"
                                >
                                    {{ currentQuestionIndex + 1 }}
                                </div>
                                <span
                                    class="text-text-muted font-label-md text-label-md uppercase tracking-wider"
                                    >QUESTION {{ currentQuestionIndex + 1 }} OF
                                    {{ totalQuestions }}</span
                                >
                            </div>
                            <h2
                                v-if="currentQuestion?.question_text"
                                class="font-headline-md text-headline-md text-text-heading mb-6 leading-snug"
                            >
                                {{ currentQuestion.question_text }}
                            </h2>
                            <div
                                class="space-y-4"
                                v-if="currentQuestion"
                            >
                                <button
                                    v-for="key in optionKeys"
                                    :key="key"
                                    @click="selectAnswer(key)"
                                    class="option-card w-full flex items-center gap-4 p-5 border-2 rounded-2xl transition-all duration-200 group cursor-pointer"
                                    :class="
                                        answers[currentQuestion.id] === key
                                            ? 'option-selected'
                                            : 'border-surface-container-high hover:border-secondary hover:bg-pastel-purple/10'
                                    "
                                >
                                    <div
                                        class="w-8 h-8 rounded-full border-2 flex-shrink-0 flex items-center justify-center font-bold transition-colors"
                                        :class="
                                            answers[currentQuestion.id] === key
                                                ? 'bg-secondary-container border-secondary text-white'
                                                : 'border-outline-variant text-text-muted group-hover:border-secondary group-hover:text-primary'
                                        "
                                    >
                                        {{ key }}
                                    </div>
                                    <span
                                        class="text-label-md text-text-muted"
                                        >Pilihan jawaban di dalam audio</span
                                    >
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </template>
        </div>

        <template #sidebar>
            <div class="flex flex-col items-center gap-2">
                <QuestionNavigator
                    :total="totalQuestions"
                    :current-index="currentQuestionIndex"
                    :answers="answeredIndices"
                    @navigate="goToQuestion"
                />
            </div>
        </template>

        <template #footer>
            <div
                class="fixed bottom-0 inset-x-0 h-16 bg-surface-white border-t border-outline-variant flex items-center px-6 gap-4 z-20"
            >
                <button
                    @click="goToQuestion(currentQuestionIndex - 1)"
                    :disabled="currentQuestionIndex === 0"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-30"
                >
                    <IconArrowLeft :size="16" /> Sebelumnya
                </button>
                <button
                    v-if="currentQuestionIndex < totalQuestions - 1"
                    @click="goToQuestion(currentQuestionIndex + 1)"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all"
                >
                    Selanjutnya <IconArrowRight :size="16" />
                </button>
                <div
                    class="flex-1 flex items-center justify-end gap-4"
                >
                    <span class="text-label-md text-text-muted">
                        Terjawab {{ answeredIds.length }}/{{ totalQuestions }}
                    </span>
                    <span
                        v-if="strikeCount > 0"
                        class="text-label-md"
                        :class="
                            strikeCount >= 3
                                ? 'text-error-red font-bold'
                                : 'text-amber-600 dark:text-amber-400'
                        "
                    >
                        ⚠ {{ strikeCount }}/3 pelanggaran
                    </span>
                    <span
                        v-if="isSaving"
                        class="text-label-md text-text-muted"
                        >Menyimpan...</span
                    >
                    <span
                        v-else-if="lastSaved"
                        class="text-label-md text-green-600 dark:text-green-400"
                        >✓ Tersimpan {{ lastSaved }}</span
                    >
                    <button
                        @click="showSubmitConfirm = true"
                        class="bg-primary-container text-white px-8 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95"
                    >
                        Kumpulkan Ujian
                    </button>
                </div>
            </div>
        </template>
    </ExamLayout>

    <div
        v-if="showSubmitConfirm"
        class="fixed inset-0 z-[200] bg-black/50 flex items-center justify-center p-6"
        @click.self="showSubmitConfirm = false"
    >
        <div
            class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center"
        >
            <h2
                class="text-headline-md font-bold text-primary mb-2"
            >
                Yakin ingin mengumpulkan?
            </h2>
            <p class="text-text-body text-body-md mb-2">
                Soal terjawab: {{ answeredIds.length }} dari
                {{ totalQuestions }}
            </p>
            <p class="text-text-muted text-label-md mb-6">
                Jawaban tidak bisa diubah setelah dikumpulkan.
            </p>
            <div class="flex gap-4">
                <BaseButton
                    variant="secondary"
                    size="lg"
                    class="flex-1"
                    @click="showSubmitConfirm = false"
                >
                    Kembali
                </BaseButton>
                <BaseButton
                    size="lg"
                    class="flex-1"
                    @click="submitExam"
                >
                    Kumpulkan
                </BaseButton>
            </div>
        </div>
    </div>

    <ViolationModal
        :show="showViolationModal"
        :message="currentViolation?.type?.replace(/_/g, ' ') || ''"
        :strike="strikeCount"
        @back="backToExam"
        @close="backToExam"
    />
</template>

<style scoped>
.scroll-hide::-webkit-scrollbar {
    width: 6px;
}
.scroll-hide::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.scroll-hide::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}
.option-card:hover {
    border-color: #5647c8;
    background-color: #f3f3f6;
}
.option-selected {
    border-color: #5647c8 !important;
    background-color: #ede9fc !important;
}
</style>
