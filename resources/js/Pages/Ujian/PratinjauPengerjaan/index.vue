<script setup>
import { Head } from "@inertiajs/vue3";
import TataLetakUjian from "@/Layouts/TataLetakUjian.vue";
import GridSoalModal from "@/Components/Ujian/GridSoalModal.vue";
import ExamIntroState from "@/Pages/Ujian/PratinjauPengerjaan/components/ExamIntroState.vue";
import SkillIntroState from "@/Pages/Ujian/PratinjauPengerjaan/components/SkillIntroState.vue";
import QuestionPartIntroState from "@/Pages/Ujian/PratinjauPengerjaan/components/QuestionPartIntroState.vue";
import ReadingQuestionPane from "@/Pages/Ujian/PratinjauPengerjaan/components/ReadingQuestionPane.vue";
import ListeningQuestionPane from "@/Pages/Ujian/PratinjauPengerjaan/components/ListeningQuestionPane.vue";
import QuestionPreviewPane from "@/Pages/Ujian/PratinjauPengerjaan/components/QuestionPreviewPane.vue";
import QuestionNavigation from "@/Pages/Ujian/PratinjauPengerjaan/components/QuestionNavigation.vue";
import { ref, computed, watch } from "vue";

const props = defineProps({
    sesiUjian: { type: Object, required: true },
    overview: { type: Object, default: () => ({}) },
    blocks: { type: Array, default: () => [] },
});

// ============================================================
// FLOW ENGINE
// steps: [{kind:'intro'}, {kind:'keterampilan', block}, {kind:'bagianSoal', block}, {kind:'soal', block}, ...]
// ============================================================

const steps = computed(() => {
    const list = [{ kind: "intro" }];
    let lastSkillCode = null;

    for (const block of props.blocks) {
        if (block.keterampilan.kode !== lastSkillCode) {
            lastSkillCode = block.keterampilan.kode;
            list.push({ kind: "keterampilan", block });
        }
        list.push({ kind: "bagianSoal", block });
        list.push({ kind: "soal", block });
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
    ["keterampilan", "bagianSoal", "soal"].includes(phase.value)
        ? currentStep.value.block
        : null,
);

const isQuestions = computed(() => phase.value === "soal");

const currentBlockQuestions = computed(() =>
    isQuestions.value ? (currentBlock.value?.soal ?? []) : [],
);

const totalInBlock = computed(() => currentBlockQuestions.value.length);

const currentQuestion = computed(() =>
    isQuestions.value
        ? (currentBlockQuestions.value[subIndex.value] ?? null)
        : null,
);

const globalNumber = computed(() =>
    isQuestions.value ? (currentBlock.value.nomorMulai + subIndex.value) : 0,
);

const totalAllQuestions = computed(() =>
    props.blocks.reduce((sum, b) => sum + b.jumlahSoal, 0),
);

const headerTitle = computed(() => {
    if (!currentBlock.value) {
        return "Informasi Ujian";
    }
    const prefix =
        [
            currentBlock.value.keterampilan.nama,
            currentBlock.value.bagianSoal.nama,
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

const answeredCount = computed(() => Object.keys(answers.value).length);

// ============================================================
// FOOTER NUMBER STRIP (per bagianSoal) — sliding window maks 5 nomor
// ============================================================

const WINDOW_SIZE = 5;

const visibleNumbers = computed(() => {
    const total = currentBlockQuestions.value.length;
    if (total <= WINDOW_SIZE) {
        return Array.from({ length: total }, (_, i) => i);
    }
    const start = Math.max(
        0,
        Math.min(subIndex.value - Math.floor(WINDOW_SIZE / 2), total - WINDOW_SIZE),
    );
    return Array.from({ length: WINDOW_SIZE }, (_, k) => start + k);
});

// Arah animasi strip: slide-next/prev saat maju/mundur 1 soal,
// tanpa animasi untuk lompatan besar (mis. via Peta Soal).
const stripAnim = ref("strip-none");
const prevGlobalNumber = ref(null);

watch(globalNumber, (value) => {
    const prev = prevGlobalNumber.value;
    prevGlobalNumber.value = value;
    if (prev === null || Math.abs(value - prev) !== 1) {
        stripAnim.value = "strip-none";
        return;
    }
    stripAnim.value = value > prev ? "strip-next" : "strip-prev";
});

const mapGroups = computed(() =>
    props.blocks.map((block) => ({
        keterampilan: { nama: block.keterampilan.nama, kode: block.keterampilan.kode },
        bagianSoal: { nama: block.bagianSoal.nama },
        nomorMulai: block.nomorMulai,
        jumlahSoal: block.jumlahSoal,
        soal: block.soal.map((q, i) => ({
            id: q.id,
            nomor_global: q.nomor_global ?? block.nomorMulai + i,
        })),
    })),
);

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
        if (steps.value[idx].kind === "soal") {
            stepIndex.value = idx;
            subIndex.value = steps.value[idx].block.soal.length - 1;
            return;
        }
        idx--;
    }
    if (stepIndex.value > 0) {
        stepIndex.value = 0;
        subIndex.value = 0;
    }
}

function goToGlobal(number) {
    for (let idx = 0; idx < steps.value.length; idx++) {
        const step = steps.value[idx];
        if (step.kind !== "soal") continue;
        const block = step.block;
        const end = block.nomorMulai + block.soal.length - 1;
        if (number >= block.nomorMulai && number <= end) {
            stepIndex.value = idx;
            subIndex.value = number - block.nomorMulai;
            return true;
        }
    }
    return false;
}

function selectAnswer(key) {
    const q = currentQuestion.value;
    if (!q) return;
    answers.value[q.id] = key;
}

function selectQuestion(index) {
    subIndex.value = index;
}

function canGoPrev() {
    if (!isQuestions.value) return stepIndex.value > 0;
    return subIndex.value > 0 || hasPrevQuestionsBlock();
}

function hasPrevQuestionsBlock() {
    for (let i = 0; i < stepIndex.value; i++) {
        if (steps.value[i].kind === "soal") return true;
    }
    return false;
}

function isLastStep() {
    return stepIndex.value === steps.value.length - 1;
}

// ============================================================
// MEDIA
// ============================================================

const materiSoal = computed(() => currentQuestion.value?.materiSoal || null);

const audioSrc = computed(() => {
    const path = materiSoal.value?.url_audio;
    return path ? "/media/" + path : null;
});

const imageSrc = computed(() => {
    const path = materiSoal.value?.url_gambar;
    return path ? "/media/" + path : null;
});

const isMaterialAudio = computed(
    () => currentQuestion.value?.materi === "audio",
);

// Timer mock statis untuk preview
const minutes = ref(50);
const seconds = ref(37);
</script>

<template>
    <Head title="Pratinjau Ujian" />
    <TataLetakUjian
        :sesiUjian="sesiUjian"
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
            <ExamIntroState
                v-if="phase === 'intro'"
                :overview="overview"
                :participant-name="sesiUjian.pengguna?.name || 'Peserta'"
                @next="goToNext"
            />
            <SkillIntroState
                v-else-if="phase === 'keterampilan'"
                :current-block="currentBlock"
                :blocks="blocks"
                :can-go-prev="canGoPrev()"
                @previous="goToPrev"
                @next="goToNext"
            />
            <QuestionPartIntroState
                v-else-if="phase === 'bagianSoal'"
                :current-block="currentBlock"
                :can-go-prev="canGoPrev()"
                @previous="goToPrev"
                @next="goToNext"
            />
        </div>

        <!-- ==================== FASE: MENGERJAKAN SOAL ==================== -->
        <div v-else class="flex h-full">
            <ReadingQuestionPane
                v-if="!isMaterialAudio"
                :materi-soal="materiSoal"
                :bagian-soal-label="currentBlock.bagianSoal.nama"
                :image-src="imageSrc"
            />
            <ListeningQuestionPane
                v-else
                :materi-soal="materiSoal"
                :bagian-soal-label="currentBlock.bagianSoal.nama"
                :image-src="imageSrc"
                :audio-src="audioSrc"
            />

            <section class="w-2/5 flex flex-col bg-surface-white">
                <QuestionPreviewPane
                    :current-question="currentQuestion"
                    :global-number="globalNumber"
                    :total-all-questions="totalAllQuestions"
                    :answered-count="answeredCount"
                    :answers="answers"
                    :is-audio="isMaterialAudio"
                    @select-answer="selectAnswer"
                />
                <QuestionNavigation
                    :current-index="subIndex"
                    :questions="currentBlockQuestions"
                    :answers="answers"
                    :visible-numbers="visibleNumbers"
                    :strip-anim="stripAnim"
                    :question-number-start="currentBlock.nomorMulai"
                    :can-go-prev="canGoPrev()"
                    :is-last-step="isLastStep()"
                    @previous="goToPrev"
                    @select-question="selectQuestion"
                    @next="goToNext"
                    @show-grid="showGridModal = true"
                />
            </section>
        </div>
    </TataLetakUjian>

    <GridSoalModal
        :show="showGridModal"
        :groups="mapGroups"
        :answers="answers"
        :current-global-number="globalNumber"
        @navigate-global="goToGlobal"
        @close="showGridModal = false"
    />
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
</style>
