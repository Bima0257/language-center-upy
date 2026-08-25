<script setup>
import { Head } from "@inertiajs/vue3";
import ExamLayout from "@/Layouts/ExamLayout.vue";
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import AudioPlayer from "@/Components/Exam/AudioPlayer.vue";
import QuestionGridModal from "@/Components/Exam/QuestionGridModal.vue";
import ViolationModal from "@/Components/Exam/ViolationModal.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useExamTimer } from "@/Modules/ExamModule/Composables/useExamTimer.js";
import { useExamSecurity } from "@/Modules/ExamModule/Composables/useExamSecurity.js";
import { useAutoSave } from "@/Modules/ExamModule/Composables/useAutoSave.js";
import { materialOfSkill, skillLabel } from "@/constants/skills";
import { IconArrowLeft, IconArrowRight, IconMap } from "@tabler/icons-vue";
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

const answeredCount = computed(() => Object.keys(answers.value).length);

// ============================================================
// FOOTER NUMBER STRIP (per section) — sliding window maks 5 nomor
// ============================================================

const WINDOW_SIZE = 5;

const visibleNumbers = computed(() => {
    const total = currentQuestions.value.length;
    if (total <= WINDOW_SIZE) {
        return Array.from({ length: total }, (_, i) => i);
    }
    const start = Math.max(
        0,
        Math.min(currentQuestionIndex.value - Math.floor(WINDOW_SIZE / 2), total - WINDOW_SIZE),
    );
    return Array.from({ length: WINDOW_SIZE }, (_, k) => start + k);
});

// Arah animasi strip: slide-next/prev saat maju/mundur 1 soal,
// tanpa animasi untuk lompatan besar (mis. via Peta Soal).
const stripAnim = ref("strip-none");
const prevQuestionNumber = ref(null);

watch(currentQuestionIndex, (value) => {
    const prev = prevQuestionNumber.value;
    prevQuestionNumber.value = value;
    if (prev === null || Math.abs(value - prev) !== 1) {
        stripAnim.value = "strip-none";
        return;
    }
    stripAnim.value = value > prev ? "strip-next" : "strip-prev";
});

// Kunci posisi asli tombol yang keluar, agar tidak teleport
// saat position:absolute diterapkan pada leave-active.
function onBeforeLeaveStrip(el) {
    el.style.left = `${el.offsetLeft}px`;
    el.style.top = `${el.offsetTop}px`;
}

const mapGroups = computed(() => {
    if (!currentQuestions.value.length) return [];
    const skillName =
        currentQuestions.value[0]?.skill?.name ||
        partLabel.value ||
        "Section";
    return [
        {
            skill: { name: skillName },
            part: { name: partLabel.value || "Soal" },
            startNumber: 1,
            count: currentQuestions.value.length,
            questions: currentQuestions.value.map((q, i) => ({
                id: q.id,
                global_number: i + 1,
            })),
        },
    ];
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
const showGridModal = ref(false);

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

const headerTitle = computed(() => {
    const prefix = [
        skillLabel(currentSkillCode.value),
        currentQuestion.value?.skillPart?.name,
    ]
        .filter(Boolean)
        .join(' ');
    return (
        (prefix ? prefix + ' — ' : '') +
        'Soal ' +
        (currentQuestionIndex.value + 1) +
        ' dari ' +
        totalQuestions.value
    );
});

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
        :title="headerTitle"
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
                    class="w-3/5 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright"
                >
                    <div class="max-w-2xl mx-auto">
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
                <section class="w-2/5 flex flex-col bg-surface-white">
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
                                    >SOAL {{ currentQuestionIndex + 1 }} DARI
                                    {{ totalQuestions }}</span
                                >
                                <div class="ml-auto flex items-center gap-2 shrink-0">
                                    <span
                                        class="text-label-md text-text-muted"
                                        >Terjawab {{ answeredCount }}/{{ totalQuestions }}</span
                                    >
                                    <span
                                        v-if="strikeCount > 0"
                                        class="shrink-0 text-label-md"
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
                                        class="shrink-0 text-label-md text-text-muted"
                                        >Menyimpan...</span
                                    >
                                    <span
                                        v-else-if="lastSaved"
                                        class="shrink-0 text-label-md text-green-600 dark:text-green-400"
                                        >✓ Tersimpan {{ lastSaved }}</span
                                    >
                                </div>
                            </div>
                            <h2
                                class="font-headline-md text-headline-md text-text-heading mb-10 leading-snug"
                            >
                                {{ currentQuestion?.question_text }}
                            </h2>
                            <div
                                class="space-y-2.5"
                                v-if="currentQuestion"
                            >
                                <button
                                    v-for="key in optionKeys"
                                    :key="key"
                                    @click="selectAnswer(key)"
                                    class="option-card w-full flex items-start gap-3 px-4 py-3 border rounded-xl text-left transition-all duration-200 group cursor-pointer"
                                    :class="
                                        answers[currentQuestion.id] === key
                                            ? 'option-selected'
                                            : 'border-surface-container-high hover:border-secondary hover:bg-pastel-purple/10'
                                    "
                                >
                                    <div
                                        class="w-7 h-7 rounded-full border flex-shrink-0 flex items-center justify-center font-semibold text-[13px] transition-colors"
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
                                        class="font-body-md text-[15px] text-on-surface-variant leading-relaxed"
                                    >
                                        {{ optionText(key) }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- NAVIGASI PER SECTION -->
                    <div
                        class="shrink-0 border-t border-outline-variant bg-surface-container-low/60 px-4 py-3.5 min-h-[68px] flex items-center gap-2"
                    >
                        <button
                            @click="goToQuestion(currentQuestionIndex - 1)"
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
                                @click="goToQuestion(i)"
                                class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-[12px] font-bold transition-all duration-150 cursor-pointer"
                                :class="[
                                    i === currentQuestionIndex
                                        ? 'bg-secondary text-white ring-2 ring-secondary/30 scale-105 shadow-sm'
                                        : answers[currentQuestions[i].id] !== undefined
                                            ? 'bg-secondary/10 text-secondary border border-secondary/40 hover:bg-secondary/20'
                                            : 'bg-surface-white text-text-muted border border-outline-variant/60 hover:bg-surface-container-highest'
                                ]"
                            >
                                {{ i + 1 }}
                            </button>
                        </TransitionGroup>

                        <button
                            v-if="currentQuestionIndex < totalQuestions - 1"
                            @click="goToQuestion(currentQuestionIndex + 1)"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold bg-primary-container text-white hover:bg-primary transition-all active:scale-95 duration-150"
                        >
                            Next <IconArrowRight :size="14" />
                        </button>
                        <button
                            v-else
                            @click="showSubmitConfirm = true"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all active:scale-95 duration-150"
                        >
                            Kumpulkan
                        </button>

                        <button
                            @click="showGridModal = true"
                            class="ml-auto shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                        >
                            <IconMap :size="14" /> Peta Soal
                        </button>
                    </div>
                </section>
            </template>

            <!-- AUDIO + GAMBAR: SPLIT PANE -->
            <template v-else>
                <!-- LEFT PANE: MEDIA -->
                <section
                    class="w-3/5 border-r border-outline-variant bg-surface-bright flex flex-col overflow-hidden"
                >
                    <!-- AREA SCROLL: badge, judul, gambar -->
                    <div class="flex-1 min-h-0 overflow-y-auto scroll-hide p-8">
                        <div class="max-w-2xl mx-auto">
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
                                v-if="imageSrc"
                                class="rounded-2xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard"
                            >
                                <div class="relative w-full min-h-[300px]">
                                    <BaseMediaLoader
                                        :loading="imageLoading.loading.value"
                                        media-type="image"
                                        skeleton-class="h-full min-h-[300px] rounded-lg"
                                    >
                                        <img
                                            :src="imageSrc"
                                            class="absolute inset-0 w-full h-full object-cover rounded-lg"
                                            @load="imageLoading.onLoad()"
                                            @error="imageLoading.onError()"
                                        />
                                    </BaseMediaLoader>
                                    <div class="absolute inset-0 bg-black/5 rounded-lg"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BAR AUDIO FIXED BAWAH -->
                    <div class="shrink-0 border-t border-outline-variant bg-surface-white">
                        <AudioPlayer v-if="audioSrc" :src="audioSrc" bar />
                        <div v-else class="px-6 py-4 text-center">
                            <p class="text-text-muted text-body-md">
                                Belum ada audio untuk soal ini.
                            </p>
                        </div>
                    </div>
                </section>
                <!-- RIGHT PANE: QUESTION -->
                <section class="w-2/5 flex flex-col bg-surface-white">
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
                                    >SOAL {{ currentQuestionIndex + 1 }} DARI
                                    {{ totalQuestions }}</span
                                >
                                <div class="ml-auto flex items-center gap-2 shrink-0">
                                    <span
                                        class="text-label-md text-text-muted"
                                        >Terjawab {{ answeredCount }}/{{ totalQuestions }}</span
                                    >
                                    <span
                                        v-if="strikeCount > 0"
                                        class="shrink-0 text-label-md"
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
                                        class="shrink-0 text-label-md text-text-muted"
                                        >Menyimpan...</span
                                    >
                                    <span
                                        v-else-if="lastSaved"
                                        class="shrink-0 text-label-md text-green-600 dark:text-green-400"
                                        >✓ Tersimpan {{ lastSaved }}</span
                                    >
                                </div>
                            </div>
                            <h2
                                v-if="currentQuestion?.question_text"
                                class="font-headline-md text-headline-md text-text-heading mb-6 leading-snug"
                            >
                                {{ currentQuestion.question_text }}
                            </h2>
                            <div
                                class="space-y-2.5"
                                v-if="currentQuestion"
                            >
                                <button
                                    v-for="key in optionKeys"
                                    :key="key"
                                    @click="selectAnswer(key)"
                                    class="option-card w-full flex items-center gap-3 px-4 py-3 border rounded-xl transition-all duration-200 group cursor-pointer"
                                    :class="
                                        answers[currentQuestion.id] === key
                                            ? 'option-selected'
                                            : 'border-surface-container-high hover:border-secondary hover:bg-pastel-purple/10'
                                    "
                                >
                                    <div
                                        class="w-7 h-7 rounded-full border flex-shrink-0 flex items-center justify-center font-semibold text-[13px] transition-colors"
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

                    <!-- NAVIGASI PER SECTION -->
                    <div
                        class="shrink-0 border-t border-outline-variant bg-surface-container-low/60 px-4 py-3.5 min-h-[68px] flex items-center gap-2"
                    >
                        <button
                            @click="goToQuestion(currentQuestionIndex - 1)"
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
                                @click="goToQuestion(i)"
                                class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-[12px] font-bold transition-all duration-150 cursor-pointer"
                                :class="[
                                    i === currentQuestionIndex
                                        ? 'bg-secondary text-white ring-2 ring-secondary/30 scale-105 shadow-sm'
                                        : answers[currentQuestions[i].id] !== undefined
                                            ? 'bg-secondary/10 text-secondary border border-secondary/40 hover:bg-secondary/20'
                                            : 'bg-surface-white text-text-muted border border-outline-variant/60 hover:bg-surface-container-highest'
                                ]"
                            >
                                {{ i + 1 }}
                            </button>
                        </TransitionGroup>

                        <button
                            v-if="currentQuestionIndex < totalQuestions - 1"
                            @click="goToQuestion(currentQuestionIndex + 1)"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold bg-primary-container text-white hover:bg-primary transition-all active:scale-95 duration-150"
                        >
                            Next <IconArrowRight :size="14" />
                        </button>
                        <button
                            v-else
                            @click="showSubmitConfirm = true"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all active:scale-95 duration-150"
                        >
                            Kumpulkan
                        </button>

                        <button
                            @click="showGridModal = true"
                            class="ml-auto shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                        >
                            <IconMap :size="14" /> Peta Soal
                        </button>
                    </div>
                </section>
            </template>
        </div>
    </ExamLayout>

    <div
        v-if="showSubmitConfirm"
        class="fixed inset-0 z-[200] bg-black/50 flex items-center justify-center p-6 backdrop-blur-sm"
        @click.self="showSubmitConfirm = false"
    >
        <div
            class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center"
        >
            <div class="w-16 h-16 bg-pastel-purple rounded-full flex items-center justify-center mx-auto mb-5">
                <span class="text-3xl">📝</span>
            </div>
            <h2
                class="text-headline-md font-bold text-text-heading mb-2"
            >
                Yakin ingin mengumpulkan?
            </h2>
            <p class="text-text-body text-body-md mb-1">
                Soal terjawab: <strong>{{ answeredCount }}</strong> dari {{ totalQuestions }}
            </p>
            <p class="text-text-muted text-label-md mb-6">
                Jawaban tidak bisa diubah setelah dikumpulkan.
            </p>
            <div class="flex gap-3">
                <button
                    @click="showSubmitConfirm = false"
                    class="flex-1 px-5 py-3 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                >
                    Kembali
                </button>
                <button
                    @click="submitExam"
                    class="flex-1 px-5 py-3 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all shadow-md active:scale-95 duration-150"
                >
                    Kumpulkan
                </button>
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

    <QuestionGridModal
        :show="showGridModal"
        :groups="mapGroups"
        :answers="answers"
        :current-global-number="currentQuestionIndex + 1"
        @navigate-global="goToQuestion"
        @close="showGridModal = false"
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
    border-color: var(--color-secondary);
    background-color: var(--color-surface-container-low);
}
.option-selected {
    border-color: var(--color-secondary) !important;
    background-color: var(--color-pastel-purple) !important;
}

/* Transisi strip nomor footer */
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
