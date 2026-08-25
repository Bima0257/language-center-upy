<script setup>
import { Head } from "@inertiajs/vue3";
import ExamLayout from "@/Layouts/ExamLayout.vue";
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import AudioPlayer from "@/Components/Exam/AudioPlayer.vue";
import QuestionNavigator from "@/Components/Exam/QuestionNavigator.vue";
import QuestionGridModal from "@/Components/Exam/QuestionGridModal.vue";
import { ref, computed } from "vue";
import {
    IconArrowLeft,
    IconArrowRight,
    IconBook,
    IconHeadphones,
    IconInfoCircle,
    IconCheck,
} from "@tabler/icons-vue";

const props = defineProps({
    session: { type: Object, required: true },
    overview: { type: Object, default: () => ({}) },
    blocks: { type: Array, default: () => [] },
});

const optionKeys = ["A", "B", "C", "D"];

// ============================================================
// FLOW ENGINE
// steps: [{kind:'intro'}, {kind:'skill', block}, {kind:'part', block}, {kind:'questions', block}, ...]
// ============================================================

const steps = computed(() => {
    const list = [{ kind: "intro" }];
    let lastSkillCode = null;

    for (const block of props.blocks) {
        if (block.skill.code !== lastSkillCode) {
            lastSkillCode = block.skill.code;
            list.push({ kind: "skill", block });
        }
        list.push({ kind: "part", block });
        list.push({ kind: "questions", block });
    }

    return list;
});

const stepIndex = ref(0);
const subIndex = ref(0);
const showGridModal = ref(false);

const currentStep = computed(
    () => steps.value[stepIndex.value] || { kind: "intro" },
);

const phase = computed(() => currentStep.value.kind);

const currentBlock = computed(() =>
    ["skill", "part", "questions"].includes(phase.value)
        ? currentStep.value.block
        : null,
);

const isQuestions = computed(() => phase.value === "questions");

const currentBlockQuestions = computed(() =>
    isQuestions.value ? (currentBlock.value?.questions ?? []) : [],
);

const totalInBlock = computed(() => currentBlockQuestions.value.length);

const currentQuestion = computed(() =>
    isQuestions.value
        ? (currentBlockQuestions.value[subIndex.value] ?? null)
        : null,
);

const globalNumber = computed(() =>
    isQuestions.value ? (currentBlock.value.startNumber + subIndex.value) : 0,
);

const totalAllQuestions = computed(() =>
    props.blocks.reduce((sum, b) => sum + b.count, 0),
);

const headerTitle = computed(() => {
    if (!currentBlock.value) {
        return "Informasi Ujian";
    }
    const prefix =
        [
            currentBlock.value.skill.name,
            currentBlock.value.part.name,
        ]
            .filter(Boolean)
            .join(" ") || "";
    if (!isQuestions.value) {
        return prefix;
    }
    return (
        prefix +
        " — Soal " +
        globalNumber.value +
        " dari " +
        totalAllQuestions.value
    );
});

// ============================================================
// ANSWER STATE
// ============================================================

const answers = ref({});

function optionText(key) {
    return currentQuestion.value
        ? currentQuestion.value["option_" + key.toLowerCase()] || ""
        : "";
}

const answeredIds = computed(() =>
    Object.keys(answers.value).map(Number),
);

const answeredIndices = computed(() => {
    return currentBlockQuestions.value
        .map((q, i) => (answers.value[q.id] !== undefined ? i : -1))
        .filter((i) => i >= 0);
});

const answerByIndex = computed(() => {
    return currentBlockQuestions.value.map((q) => answers.value[q.id] || null);
});

// ============================================================
// NAVIGATION
// ============================================================

function goToNext() {
    if (isQuestions.value && subIndex.value < totalInBlock.value - 1) {
        subIndex.value++;
        return;
    }
    if (stepIndex.value < steps.value.length - 1) {
        stepIndex.value++;
        subIndex.value = 0;
    }
}

function goToPrev() {
    if (isQuestions.value && subIndex.value > 0) {
        subIndex.value--;
        return;
    }
    // mundur ke blok soal sebelumnya jika ada, masuk ke soal terakhirnya
    let idx = stepIndex.value - 1;
    while (idx >= 0) {
        if (steps.value[idx].kind === "questions") {
            stepIndex.value = idx;
            subIndex.value = steps.value[idx].block.questions.length - 1;
            return;
        }
        idx--;
    }
    if (stepIndex.value > 0) {
        stepIndex.value = 0;
        subIndex.value = 0;
    }
}

function goToQuestion(index) {
    if (index >= 0 && index < totalInBlock.value) {
        subIndex.value = index;
    }
}

function selectAnswer(key) {
    const q = currentQuestion.value;
    if (!q) return;
    answers.value[q.id] = key;
}

function canGoPrev() {
    if (!isQuestions.value) return stepIndex.value > 0;
    return subIndex.value > 0 || hasPrevQuestionsBlock();
}

function hasPrevQuestionsBlock() {
    for (let i = 0; i < stepIndex.value; i++) {
        if (steps.value[i].kind === "questions") return true;
    }
    return false;
}

function isLastStep() {
    return stepIndex.value === steps.value.length - 1;
}

const nextLabel = computed(() => {
    if (phase.value === "intro") return "Mulai";
    if (phase.value === "skill") return "Lanjut";
    if (phase.value === "part") return "Mulai Mengerjakan";
    return "Lanjut";
});

// ============================================================
// MEDIA
// ============================================================

const passage = computed(() => currentQuestion.value?.passage || null);

const audioSrc = computed(() => {
    const path = passage.value?.audio_url;
    return path ? "/media/" + path : null;
});

const imageSrc = computed(() => {
    const path = passage.value?.image_url;
    return path ? "/media/" + path : null;
});

const isMaterialAudio = computed(
    () => currentBlock.value?.skill?.code === "listening",
);

// Timer mock statis untuk preview
const minutes = ref(50);
const seconds = ref(37);
</script>

<template>
    <Head title="Preview Ujian" />
    <ExamLayout
        :session="session"
        :title="headerTitle"
        :remaining-seconds="minutes * 60 + seconds"
        :minutes="minutes"
        :seconds="seconds"
        :is-warning="false"
        :is-danger="false"
        :show-timer="isQuestions"
    >
        <!-- ==================== FASE: INTRO / SKILL / PART ==================== -->
        <div
            v-if="!isQuestions"
            class="h-full overflow-y-auto scroll-hide flex items-center justify-center p-8 bg-surface-bright"
        >
            <!-- INTRO: INFORMASI UJIAN -->
            <div
                v-if="phase === 'intro'"
                class="bg-surface-white rounded-3xl shadow-standard border border-outline-variant/30 max-w-3xl w-full overflow-hidden"
            >
                <div class="bg-primary-container px-8 py-7 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <IconInfoCircle :size="22" />
                        <span class="text-label-md uppercase tracking-widest opacity-70 font-semibold">Informasi Ujian</span>
                    </div>
                    <h1 class="text-headline-md font-bold">{{ overview.examTitle || 'Preview Ujian' }}</h1>
                </div>
                <div class="px-8 py-6">
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="rounded-xl bg-surface-container-low px-4 py-3">
                            <p class="text-label-md text-text-muted mb-1">Durasi</p>
                            <p class="text-title-lg font-bold text-text-heading">{{ overview.durationMinutes }} menit</p>
                        </div>
                        <div class="rounded-xl bg-surface-container-low px-4 py-3">
                            <p class="text-label-md text-text-muted mb-1">Total Soal</p>
                            <p class="text-title-lg font-bold text-text-heading">{{ overview.totalQuestions }}</p>
                        </div>
                        <div class="rounded-xl bg-surface-container-low px-4 py-3">
                            <p class="text-label-md text-text-muted mb-1">Peserta</p>
                            <p class="text-title-lg font-bold text-text-heading truncate">{{ session.user?.name || 'Peserta' }}</p>
                        </div>
                    </div>

                    <p class="text-body-md text-text-body mb-5 leading-relaxed">
                        Bacalah petunjuk setiap bagian dengan teliti sebelum mengerjakan.
                        Soal akan dimuat secara berurutan per skill dan part. Nomor soal
                        berjalan menerus dari bagian pertama sampai bagian terakhir.
                    </p>

                    <div class="space-y-3 mb-2">
                        <div
                            v-for="skill in overview.skills || []"
                            :key="skill.code"
                            class="rounded-2xl border border-outline-variant/40 p-4"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <IconBook v-if="skill.code === 'reading'" :size="18" class="text-secondary" />
                                <IconHeadphones v-else :size="18" class="text-secondary" />
                                <span class="font-bold text-text-heading">{{ skill.name }}</span>
                                <span class="ml-auto text-label-md text-text-muted">{{ skill.totalQuestions }} soal</span>
                            </div>
                            <div class="flex flex-wrap gap-2 pl-7">
                                <span
                                    v-for="part in skill.parts"
                                    :key="part.name"
                                    class="px-3 py-1 rounded-full bg-pastel-blue/60 text-primary text-[12px] font-semibold"
                                >{{ part.name }} · {{ part.totalQuestions }} soal</span>
                            </div>
                        </div>
                    </div>

                    <button
                        @click="goToNext"
                        class="mt-6 w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all shadow-md active:scale-[0.98] duration-150"
                    >
                        Mulai <IconArrowRight :size="16" />
                    </button>
                </div>
            </div>

            <!-- INTRO: SKILL -->
            <div
                v-else-if="phase === 'skill'"
                class="bg-surface-white rounded-3xl shadow-standard border border-outline-variant/30 max-w-3xl w-full overflow-hidden"
            >
                <div
                    class="bg-primary-container px-8 py-7 flex items-center gap-4 text-white"
                >
                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                        :class="currentBlock.skill.code === 'reading' ? 'bg-pastel-blue' : 'bg-pastel-purple'"
                    >
                        <IconHeadphones
                            v-if="currentBlock.skill.code === 'listening'"
                            :size="28" class="text-secondary"
                        />
                        <IconBook v-else :size="28" class="text-secondary" />
                    </div>
                    <div>
                        <span class="text-label-md uppercase tracking-widest opacity-70 font-semibold">Section</span>
                        <h1 class="text-headline-md font-bold">{{ currentBlock.skill.name }}</h1>
                    </div>
                </div>
                <div class="px-8 py-6">
                    <RichTextViewer
                        :content="currentBlock.skill.description"
                        class="text-body-md leading-relaxed mb-5"
                    />
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span
                            v-for="(b, i) in blocks.filter(x => x.skill.code === currentBlock.skill.code)"
                            :key="i"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-surface-container-low text-label-md font-semibold text-text-heading"
                        >
                            <IconCheck v-if="false" :size="13" />
                            {{ b.part.name }} · Soal {{ b.startNumber }}–{{ b.startNumber + b.count - 1 }}
                        </span>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button
                            v-if="canGoPrev()"
                            @click="goToPrev"
                            class="flex items-center justify-center gap-2 px-5 py-3 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                        >
                            <IconArrowLeft :size="16" /> Kembali
                        </button>
                        <button
                            @click="goToNext"
                            class="col-span-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all shadow-md active:scale-[0.98] duration-150"
                            :class="{ '!col-span-1': canGoPrev() }"
                        >
                            Lanjut <IconArrowRight :size="16" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- INTRO: PART -->
            <div
                v-else-if="phase === 'part'"
                class="bg-surface-white rounded-3xl shadow-standard border border-outline-variant/30 max-w-3xl w-full overflow-hidden"
            >
                <div class="bg-primary-container px-8 py-7 text-white flex items-center justify-between">
                    <div>
                        <span class="text-label-md uppercase tracking-widest opacity-70 font-semibold">{{ currentBlock.skill.name }}</span>
                        <h1 class="text-headline-md font-bold">{{ currentBlock.part.name }}</h1>
                    </div>
                    <div class="text-right">
                        <span class="text-label-md uppercase tracking-widest opacity-70 font-semibold">Soal</span>
                        <p class="text-title-lg font-bold">{{ currentBlock.startNumber }}–{{ currentBlock.startNumber + currentBlock.count - 1 }}</p>
                    </div>
                </div>
                <div class="px-8 py-6">
                    <p class="text-label-md uppercase tracking-wider text-text-muted mb-2">Petunjuk Pengerjaan</p>
                    <RichTextViewer
                        :content="currentBlock.part.directions"
                        class="text-body-md leading-relaxed mb-6"
                    />
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-if="canGoPrev()"
                            @click="goToPrev"
                            class="flex items-center justify-center gap-2 px-5 py-3 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                        >
                            <IconArrowLeft :size="16" /> Kembali
                        </button>
                        <button
                            @click="goToNext"
                            class="flex items-center justify-center gap-2 px-5 py-3.5 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all shadow-md active:scale-[0.98] duration-150 col-span-full"
                            :class="{ '!col-span-1': canGoPrev() }"
                        >
                            Mulai Mengerjakan <IconArrowRight :size="16" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== FASE: MENGERJAKAN SOAL ==================== -->
        <div v-else class="flex h-full">
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
                            >{{ currentBlock.part.name }}</span>
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
                            v-if="imageSrc"
                            class="my-6 rounded-xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard"
                        >
                            <img
                                :src="imageSrc"
                                class="w-full h-auto rounded-lg"
                            />
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
                                    {{ globalNumber }}
                                </div>
                                <span
                                    class="text-text-muted font-label-md text-label-md uppercase tracking-wider"
                                >SOAL {{ globalNumber }} DARI {{ totalAllQuestions }}</span>
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

            <!-- LISTENING: MEDIA + SOAL -->
            <template v-else>
                <!-- LEFT PANE: MEDIA -->
                <section
                    class="w-1/2 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright"
                >
                    <div class="max-w-xl mx-auto">
                        <div class="mb-8">
                            <span
                                class="bg-pastel-purple text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block"
                            >{{ currentBlock.part.name }}</span>
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
                                {{ currentBlock.part.name }}
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
                                <img
                                    :src="imageSrc"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />
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
                                    {{ globalNumber }}
                                </div>
                                <span
                                    class="text-text-muted font-label-md text-label-md uppercase tracking-wider"
                                >SOAL {{ globalNumber }} DARI {{ totalAllQuestions }}</span>
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
        </div>

        <template v-if="isQuestions" #sidebar>
            <div class="flex flex-col items-center gap-2">
                <QuestionNavigator
                    :total="totalInBlock"
                    :current-index="subIndex"
                    :answers="answeredIndices"
                    @navigate="goToQuestion"
                    @show-grid="showGridModal = true"
                />
            </div>
        </template>

        <template v-if="isQuestions" #footer>
            <div
                class="h-16 border-t border-outline-variant px-6 flex justify-between items-center bg-surface-container-low shrink-0"
            >
                <button
                    @click="goToPrev"
                    :disabled="!canGoPrev()"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-label-md font-medium border border-outline-variant bg-surface-white text-primary hover:bg-surface-container-low transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <IconArrowLeft :size="16" /> Sebelumnya
                </button>
                <div class="flex items-center gap-4">
                    <span class="text-label-md text-text-muted">
                        Terjawab {{ answeredIds.length }}/{{ totalAllQuestions }}
                    </span>
                </div>
                <button
                    v-if="!isLastStep()"
                    @click="goToNext"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-label-md font-semibold bg-primary-container text-white hover:bg-primary transition-all shadow-md active:scale-95 duration-150"
                >
                    {{ nextLabel }} <IconArrowRight :size="16" />
                </button>
                <button
                    v-else
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-label-md font-semibold bg-secondary text-white hover:bg-secondary/90 transition-all shadow-md active:scale-95 duration-150"
                >
                    Selesai
                </button>
            </div>
        </template>
    </ExamLayout>

    <QuestionGridModal
        :show="showGridModal"
        :total="totalInBlock"
        :current-index="subIndex"
        :answer-by-index="answerByIndex"
        :answered-count="answeredIds.length"
        @navigate="goToQuestion"
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
</style>
