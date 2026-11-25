<script setup>
import TataLetakUjian from "@/Layouts/TataLetakUjian.vue";
import TimerDisplay from "@/Layouts/Ujian/Components/TimerDisplay.vue";
import GridSoalModal from "@/Components/Ujian/GridSoalModal.vue";
import ExamIntroState from "@/Components/Ujian/ExamTakePreview/ExamIntroState.vue";
import QuestionPartIntroState from "@/Components/Ujian/ExamTakePreview/QuestionPartIntroState.vue";
import ReadingQuestionPane from "@/Components/Ujian/ExamTakePreview/ReadingQuestionPane.vue";
import ListeningQuestionPane from "@/Components/Ujian/ExamTakePreview/ListeningQuestionPane.vue";
import QuestionPreviewPane from "@/Components/Ujian/ExamTakePreview/QuestionPreviewPane.vue";
import QuestionNavigation from "@/Components/Ujian/ExamTakePreview/QuestionNavigation.vue";
import DemoSummaryState from "@/Components/Ujian/ExamTakePreview/DemoSummaryState.vue";
import { onMounted, onUnmounted, ref, computed, watch } from "vue";
import axios from "axios";
import {
    IconArrowLeft,
    IconMaximize,
    IconMinimize,
    IconMoon,
    IconSun,
} from "@tabler/icons-vue";

const props = defineProps({
    sesiUjian: { type: Object, required: true },
    overview: { type: Object, default: () => ({}) },
    blocks: { type: Array, default: () => [] },
    backUrl: { type: String, default: null },
    initialSoalId: { type: String, default: null },
    demo: { type: Boolean, default: false },
    skorUrl: { type: String, default: null },
});

// ============================================================
// FLOW ENGINE
// steps: [{kind:'intro'}, {kind:'section', block}, {kind:'partSoal', block}, {kind:'soal', block}, ...]
// ============================================================

function partKey(block) {
    const kodeSection = block?.section?.kode ?? "";
    const kunciPart = String(
        block?.partSoal?.id
            ?? block?.partSoal?.nama
            ?? block?.nomorMulai
            ?? "none",
    );

    return `${kodeSection}:${kunciPart}`;
}

const steps = computed(() => {
    const list = [{ kind: "intro" }];
    let lastPartKey = null;

    for (const block of props.blocks) {
        const currentPartKey = partKey(block);

        if (currentPartKey !== lastPartKey) {
            lastPartKey = currentPartKey;
            list.push({ kind: "partSoal", block });
        }

        list.push({ kind: "soal", block });
    }

    return list;
});

const stepIndex = ref(0);
const subIndex = ref(0);
const showGridModal = ref(false);
const previewTheme = ref("light");
const isFullscreen = ref(false);
let previousDarkTheme = false;

function togglePreviewTheme() {
    previewTheme.value = previewTheme.value === "dark" ? "light" : "dark";
    document.documentElement.classList.toggle("dark", previewTheme.value === "dark");
}

function syncFullscreenState() {
    isFullscreen.value = Boolean(document.fullscreenElement);
}

function toggleFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen()?.catch(() => {});
        return;
    }

    document.documentElement.requestFullscreen()?.catch(() => {});
}

function setInitialQuestion() {
    if (!props.initialSoalId) return;

    const targetStepIndex = steps.value.findIndex(
        (step) => step.kind === "soal" && step.block.soal.some(
            (soal) => String(soal.id) === String(props.initialSoalId),
        ),
    );

    if (targetStepIndex === -1) return;

    stepIndex.value = targetStepIndex;
    subIndex.value = steps.value[targetStepIndex].block.soal.findIndex(
        (soal) => String(soal.id) === String(props.initialSoalId),
    );
}

onMounted(() => {
    previousDarkTheme = document.documentElement.classList.contains("dark");
    document.documentElement.classList.toggle(
        "dark",
        previewTheme.value === "dark",
    );
    document.addEventListener("fullscreenchange", syncFullscreenState);
    setInitialQuestion();
});

onUnmounted(() => {
    document.documentElement.classList.toggle("dark", previousDarkTheme);
    document.removeEventListener("fullscreenchange", syncFullscreenState);

    if (document.fullscreenElement) {
        document.exitFullscreen()?.catch(() => {});
    }
});

const currentStep = computed(
    () => steps.value[stepIndex.value] || { kind: "intro" },
);

const phase = computed(() => currentStep.value.kind);

const currentBlock = computed(() =>
    ["partSoal", "soal"].includes(phase.value)
        ? currentStep.value.block
        : null,
);

const activeSectionCode = computed(
    () =>
        currentBlock.value?.section?.kode ||
        props.blocks[0]?.section?.kode ||
        "",
);

const sectionTabs = computed(() => {
    const seen = new Set();

    return props.blocks
        .filter((block) => {
            const code = block.section?.kode;
            if (!code || seen.has(code)) return false;

            seen.add(code);
            return true;
        })
        .map((block) => ({
            value: block.section.kode,
            label: block.section.nama,
        }));
});

const partTabs = computed(() => {
    const seen = new Set();

    return props.blocks
        .filter((block) => block.section?.kode === activeSectionCode.value)
        .filter((block) => {
            const key = partKey(block);
            if (seen.has(key)) return false;

            seen.add(key);
            return true;
        })
        .map((block) => {
            const sectionName = block.section?.nama || "";
            const partName = block.partSoal?.nama || "Part Soal";

            return {
                value: partKey(block),
                label:
                    sectionName &&
                    partName
                        .toLowerCase()
                        .startsWith(`${sectionName.toLowerCase()} `)
                        ? partName.slice(sectionName.length).trim()
                        : partName,
            };
        });
});

const activePartValue = computed(() =>
    currentBlock.value ? partKey(currentBlock.value) : "",
);

const currentPartBlocks = computed(() => {
    if (!currentBlock.value) return [];

    const key = partKey(currentBlock.value);

    return props.blocks.filter((block) => partKey(block) === key);
});

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

const examTitle = computed(
    () => props.overview.judulUjian || "Pratinjau Ujian",
);

// ============================================================
// ANSWER STATE
// ============================================================

const answers = ref({});

const answeredCount = computed(() => Object.keys(answers.value).length);

const progressPercent = computed(() =>
    totalAllQuestions.value
        ? Math.round((answeredCount.value / totalAllQuestions.value) * 100)
        : 0,
);

// ============================================================
// DEMO STATE
// ============================================================

const demoSelesai = ref(false);
const demoMulaiPada = ref(Date.now());
const demoSelesaiPada = ref(null);
const hasilPenilaian = ref(null);
const sedangMenilai = ref(false);
const errorPenilaian = ref(null);

const durasiDemoMenit = computed(() => {
    const selesai = demoSelesaiPada.value ?? Date.now();

    return Math.max(1, Math.ceil((selesai - demoMulaiPada.value) / 60000));
});

const ringkasanSection = computed(() => {
    const ringkasan = new Map();

    for (const block of props.blocks) {
        const kode = block.section?.kode || "lainnya";
        const nama = block.section?.nama || "Section";

        if (!ringkasan.has(kode)) {
            ringkasan.set(kode, { kode, nama, total: 0, terjawab: 0 });
        }

        const item = ringkasan.get(kode);

        for (const soal of block.soal || []) {
            item.total += 1;

            if (answers.value[soal.id] !== undefined) {
                item.terjawab += 1;
            }
        }
    }

    return [...ringkasan.values()];
});

// ============================================================
// FOOTER NUMBER STRIP (per partSoal) — sliding window maks 5 nomor
// ============================================================

const WINDOW_SIZE = 5;

const allQuestions = computed(() => {
    const list = [];

    for (const block of props.blocks) {
        (block.soal ?? []).forEach((soal, index) => {
            list.push({
                id: soal.id,
                number: soal.nomor_global ?? block.nomorMulai + index,
            });
        });
    }

    return list.sort((left, right) => left.number - right.number);
});

const firstQuestionNumber = computed(() => allQuestions.value[0]?.number ?? 0);
const lastQuestionNumber = computed(
    () => allQuestions.value[allQuestions.value.length - 1]?.number ?? 0,
);

const answeredNumbers = computed(() =>
    allQuestions.value
        .filter((question) => answers.value[question.id] !== undefined)
        .map((question) => question.number),
);

const blockNumbers = computed(() =>
    currentBlockQuestions.value.map((_, index) => currentBlock.value.nomorMulai + index),
);

const visibleNumbers = computed(() => {
    if (currentBlockQuestions.value.length === 1) {
        const list = allQuestions.value;
        const index = list.findIndex((question) => question.number === globalNumber.value);

        if (index === -1) return [];

        const start = Math.max(
            0,
            Math.min(index - Math.floor(WINDOW_SIZE / 2), list.length - WINDOW_SIZE),
        );

        return list.slice(start, start + WINDOW_SIZE).map((question) => question.number);
    }

    const total = currentBlockQuestions.value.length;

    if (total <= WINDOW_SIZE) {
        return blockNumbers.value;
    }

    const start = Math.max(
        0,
        Math.min(subIndex.value - Math.floor(WINDOW_SIZE / 2), total - WINDOW_SIZE),
    );

    return blockNumbers.value.slice(start, start + WINDOW_SIZE);
});

const navigationFirstNumber = computed(() =>
    currentBlockQuestions.value.length === 1
        ? firstQuestionNumber.value
        : (currentBlock.value?.nomorMulai ?? 0),
);

const navigationLastNumber = computed(() =>
    currentBlockQuestions.value.length === 1
        ? lastQuestionNumber.value
        : (currentBlock.value?.nomorMulai ?? 0) + Math.max(currentBlockQuestions.value.length - 1, 0),
);

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

const mapGroups = computed(() => {
    const groups = new Map();

    for (const block of props.blocks) {
        const key = partKey(block);

        if (!groups.has(key)) {
            groups.set(key, {
                section: {
                    nama: block.section.nama,
                    kode: block.section.kode,
                },
                partSoal: { nama: block.partSoal.nama },
                nomorMulai: block.nomorMulai,
                jumlahSoal: 0,
                soal: [],
            });
        }

        const group = groups.get(key);

        for (const [index, soal] of block.soal.entries()) {
            group.soal.push({
                id: soal.id,
                nomor_global: soal.nomor_global ?? block.nomorMulai + index,
            });
        }

        group.jumlahSoal = group.soal.length;
    }

    return [...groups.values()];
});

// ============================================================
// NAVIGATION
// ============================================================

function goToNext() {
    if (isQuestions.value && subIndex.value < totalInBlock.value - 1) {
        subIndex.value++;
        return;
    }
    if (props.demo && isLastStep()) {
        selesaikanDemo();
        return;
    }
    if (stepIndex.value < steps.value.length - 1) {
        stepIndex.value++;
        subIndex.value = 0;
    }
}

function selesaikanDemo() {
    demoSelesaiPada.value = Date.now();
    demoSelesai.value = true;
    hitungSkorDemo();
}

async function hitungSkorDemo() {
    if (!props.skorUrl) {
        errorPenilaian.value = "Endpoint penilaian demo tidak tersedia.";
        return;
    }

    sedangMenilai.value = true;
    errorPenilaian.value = null;

    try {
        const jawaban = Object.entries(answers.value).map(([soal_id, teks]) => ({
            soal_id,
            jawaban: teks,
        }));

        const { data } = await axios.post(
            props.skorUrl,
            { jawaban },
            { headers: { Accept: "application/json" } },
        );

        hasilPenilaian.value = data;
    } catch (error) {
        errorPenilaian.value =
            error.response?.data?.message ||
            "Gagal menghitung skor demo. Silakan coba lagi.";
    } finally {
        sedangMenilai.value = false;
    }
}

function ulangiDemo() {
    answers.value = {};
    stepIndex.value = 0;
    subIndex.value = 0;
    demoMulaiPada.value = Date.now();
    demoSelesaiPada.value = null;
    demoSelesai.value = false;
    hasilPenilaian.value = null;
    sedangMenilai.value = false;
    errorPenilaian.value = null;
}

function navigateToBlock(block, kind = "partSoal") {
    if (!block) return;

    const targetIndex = steps.value.findIndex(
        (step) => step.kind === kind && step.block === block,
    );

    if (targetIndex === -1) return;

    stepIndex.value = targetIndex;
    subIndex.value = 0;
}

function selectSection(value) {
    const sectionCode = String(value);

    navigateToBlock(
        props.blocks.find(
            (block) => block.section?.kode === sectionCode,
        ),
    );
}

function selectPart(value) {
    const block = props.blocks.find(
        (candidate) =>
            candidate.section?.kode === activeSectionCode.value &&
            partKey(candidate) === String(value),
    );

    navigateToBlock(block);
}

function goBackFromPreview() {
    if (props.backUrl) {
        window.location.href = props.backUrl;
        return;
    }

    if (window.history.length > 1) {
        window.history.back();
        return;
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

const materiSoal = computed(
    () => currentQuestion.value?.materiSoal || currentQuestion.value?.materi_soal || null,
);

const audioSrc = computed(() => {
    const path = materiSoal.value?.url_audio;
    return path ? "/media/" + path : null;
});

const imageSrc = computed(() => {
    const path = materiSoal.value?.url_gambar;
    return path ? "/media/" + path : null;
});

const isMaterialAudio = computed(
    () => Boolean(materiSoal.value) && currentQuestion.value?.materi === "audio",
);

const isStandaloneQuestion = computed(() => !materiSoal.value);

// Timer mock statis untuk preview
const minutes = ref(50);
const seconds = ref(37);
</script>

<template>
    <TataLetakUjian
        :sesi-ujian="sesiUjian"
        :title="examTitle"
        :remaining-seconds="minutes * 60 + seconds"
        :minutes="minutes"
        :seconds="seconds"
        :is-warning="false"
        :is-danger="false"
        :show-timer="isQuestions"
        :soft-header="previewTheme === 'light'"
        :surface-header="previewTheme === 'dark'"
        compact-header
    >
        <template #header>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="exam-back-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 font-semibold"
                        :class="
                            previewTheme === 'dark'
                                ? 'exam-control-dark'
                                : 'exam-control-light'
                        "
                        aria-label="Kembali"
                        title="Kembali"
                        @click="goBackFromPreview"
                    >
                        <IconArrowLeft :size="20" />
                    </button>
                    <span
                        class="exam-header-divider h-7 w-px shrink-0"
                        :class="
                            previewTheme === 'dark'
                                ? 'exam-header-divider-dark'
                                : 'exam-header-divider-light'
                        "
                        aria-hidden="true"
                    ></span>
                    <div class="min-w-0">
                        <div class="flex min-w-0 items-center gap-2">
                            <p
                                class="truncate text-base font-bold tracking-[-0.02em] sm:text-lg"
                                :class="
                                    previewTheme === 'dark'
                                        ? 'text-text-heading'
                                        : 'text-text-heading'
                                "
                            >
                                {{ examTitle }}
                            </p>
                            <span
                                v-if="demo"
                                class="shrink-0 rounded-full bg-pastel-peach px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-text-pastel"
                            >
                                Mode Demo
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <div
                        v-if="isQuestions && !demoSelesai"
                        class="exam-header-timer flex items-center rounded-xl border px-3 py-1"
                        :class="
                            previewTheme === 'dark'
                                ? 'exam-header-timer-dark'
                                : 'exam-header-timer-light'
                        "
                    >
                        <TimerDisplay
                            :remaining="minutes * 60 + seconds"
                            :minutes="minutes"
                            :seconds="seconds"
                            :is-warning="false"
                            :is-danger="false"
                            :theme="previewTheme"
                            class="origin-center scale-90"
                        />
                    </div>
                    <div
                        class="exam-participant-status flex items-center gap-2 text-xs sm:text-sm"
                        :class="
                                previewTheme === 'dark'
                                    ? 'text-text-heading'
                                    : 'text-text-heading'
                        "
                        :aria-label="`Peserta: ${sesiUjian.pengguna?.name || 'Peserta'}`"
                        :title="sesiUjian.pengguna?.name || 'Peserta'"
                    >
                        <span
                            class="inline-block h-2 w-2 animate-pulse rounded-full bg-success"
                        ></span>
                        <span class="hidden sm:inline">{{ sesiUjian.pengguna?.name || "Peserta" }}</span>
                    </div>
                    <span
                        class="exam-header-divider h-7 w-px shrink-0"
                        :class="
                            previewTheme === 'dark'
                                ? 'exam-header-divider-dark'
                                : 'exam-header-divider-light'
                        "
                        aria-hidden="true"
                    ></span>
                    <button
                        type="button"
                        class="exam-theme-button inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 sm:h-10 sm:w-10 font-semibold"
                        :class="
                                previewTheme === 'dark'
                                    ? 'text-text-heading hover:bg-surface-container-high'
                                    : 'text-text-heading hover:bg-surface-container-high'
                        "
                        :aria-label="
                            previewTheme === 'dark'
                                ? 'Gunakan mode terang'
                                : 'Gunakan mode gelap'
                        "
                        :title="
                            previewTheme === 'dark'
                                ? 'Mode terang'
                                : 'Mode gelap'
                        "
                        @click="togglePreviewTheme"
                    >
                        <IconSun v-if="previewTheme === 'light'" :size="18" />
                        <IconMoon v-else :size="18" />
                    </button>
                    <button
                        type="button"
                        class="exam-theme-button inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 sm:h-10 sm:w-10 font-semibold"
                        :class="
                            previewTheme === 'dark'
                                ? 'text-text-heading hover:bg-surface-container-high'
                                : 'text-text-heading hover:bg-surface-container-high'
                        "
                        :aria-label="
                            isFullscreen
                                ? 'Keluar dari layar penuh'
                                : 'Tampilkan layar penuh'
                        "
                        :title="
                            isFullscreen
                                ? 'Keluar layar penuh'
                                : 'Layar penuh'
                        "
                        @click="toggleFullscreen"
                    >
                        <IconMinimize v-if="isFullscreen" :size="18" />
                        <IconMaximize v-else :size="18" />
                    </button>
                </div>
            </div>
        </template>

        <div v-if="!demoSelesai" class="flex h-full min-h-0 flex-col bg-canvas">
            <nav
                v-if="blocks.length && phase !== 'intro'"
                class="exam-section-nav shrink-0 px-3 pt-3 sm:px-4 sm:pt-4 lg:px-5 lg:pt-4"
                aria-label="Navigasi part ujian"
            >
                <div
                    class="exam-section-card mx-auto grid w-full min-w-0 grid-cols-1 items-center gap-2 rounded-2xl border bg-surface-white px-3 py-2.5 shadow-soft sm:px-4 lg:grid-cols-[auto_1px_minmax(0,1fr)_auto] lg:gap-3"
                >
                    <div class="exam-section-tabs min-w-0 justify-self-center lg:col-start-1 lg:justify-self-start">
                        <div class="exam-tabs-scroll flex max-w-full items-center overflow-x-auto rounded-full border p-0.5">
                            <button
                                v-for="section in sectionTabs"
                                :key="section.value"
                                type="button"
                                class="exam-tab flex min-h-8 shrink-0 items-center justify-center rounded-full px-2.5 text-xs font-semibold transition-colors sm:min-w-[112px] sm:px-3 sm:text-sm"
                                :class="
                                    String(activeSectionCode) === String(section.value)
                                        ? 'exam-tab-active'
                                        : 'exam-tab-inactive'
                                "
                                :aria-selected="String(activeSectionCode) === String(section.value)"
                                @click="selectSection(section.value)"
                            >
                                {{ section.label }}
                            </button>
                        </div>
                    </div>
                    <span
                        class="exam-nav-divider hidden h-7 w-px lg:col-start-2 lg:block"
                        aria-hidden="true"
                    ></span>
                    <div class="exam-part-tabs min-w-0 justify-self-center lg:col-start-3 lg:justify-self-start">
                        <div class="exam-tabs-scroll flex max-w-full items-center overflow-x-auto rounded-full border p-0.5">
                            <button
                                v-for="part in partTabs"
                                :key="part.value"
                                type="button"
                                class="exam-tab min-h-8 min-w-16 shrink-0 rounded-full px-2.5 text-xs font-semibold transition-colors sm:text-sm"
                                :class="
                                    String(activePartValue) === String(part.value)
                                        ? 'exam-tab-active'
                                        : 'exam-tab-inactive'
                                "
                                :aria-selected="String(activePartValue) === String(part.value)"
                                @click="selectPart(part.value)"
                            >
                                {{ part.label }}
                            </button>
                        </div>
                    </div>
                    <div
                        class="flex shrink-0 items-center justify-center gap-1 text-xs text-text-muted lg:col-start-4 lg:justify-end"
                        aria-label="Progress ujian"
                    >
                        <span class="hidden sm:inline">Progress</span>
                        <div
                            class="exam-progress-track h-2 w-24 overflow-hidden rounded-full sm:w-32"
                            role="progressbar"
                            :aria-valuenow="progressPercent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-label="`${answeredCount} dari ${totalAllQuestions} soal terjawab`"
                        >
                            <div
                                class="exam-progress-value h-full rounded-full transition-[width] duration-300 motion-reduce:transition-none"
                                :style="{ width: `${progressPercent}%` }"
                            ></div>
                        </div>
                        <span class="tabular-nums text-xs font-semibold text-text-heading"
                            >{{ answeredCount }}/{{ totalAllQuestions }}</span
                        >
                    </div>
                </div>
            </nav>

            <div
                v-if="!isQuestions"
                class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain scroll-hide bg-canvas p-3 sm:p-4"
            >
                <div class="flex min-h-full items-center justify-center">
                    <ExamIntroState
                        v-if="phase === 'intro'"
                        :overview="overview"
                        :participant-name="sesiUjian.pengguna?.name || 'Peserta'"
                        @next="goToNext"
                    />
                    <QuestionPartIntroState
                        v-else-if="phase === 'partSoal'"
                        :current-block="currentBlock"
                        :part-blocks="currentPartBlocks"
                        :can-go-prev="canGoPrev()"
                        @previous="goToPrev"
                        @next="goToNext"
                    />
                </div>
            </div>

            <div
                v-else
                class="min-h-0 flex-1 overflow-hidden bg-canvas p-3 sm:p-4 lg:p-5"
            >
                <div
                    class="exam-workspace-grid mx-auto grid h-full min-h-0 max-w-[1480px] grid-cols-1 gap-4 sm:gap-5 lg:gap-5"
                    :class="
                        isStandaloneQuestion
                            ? 'lg:grid-cols-1'
                            : 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]'
                    "
                >
                    <ReadingQuestionPane
                        v-if="materiSoal && !isMaterialAudio"
                        :materi-soal="materiSoal"
                        :image-src="imageSrc"
                    />
                    <ListeningQuestionPane
                        v-else-if="materiSoal && isMaterialAudio"
                        :materi-soal="materiSoal"
                        :image-src="imageSrc"
                        :audio-src="audioSrc"
                    />

                    <section
                        class="preview-question-panel flex min-h-0 min-w-0 flex-col overflow-hidden rounded-[20px] border bg-surface-container-low"
                        :class="{
                            'mx-auto w-full max-w-3xl self-start max-h-full':
                                isStandaloneQuestion,
                        }"
                    >
                        <QuestionPreviewPane
                            :current-question="currentQuestion"
                            :global-number="globalNumber"
                            :total-all-questions="totalAllQuestions"
                            :answers="answers"
                            :is-audio="isMaterialAudio"
                            :show-question-text="true"
                            @select-answer="selectAnswer"
                        />
                    </section>
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex h-full min-h-0 items-center justify-center overflow-y-auto overscroll-y-contain bg-canvas p-3 sm:p-4"
        >
            <DemoSummaryState
                :overview="overview"
                :participant-name="sesiUjian.pengguna?.name || 'Pratinjau'"
                :answered-count="answeredCount"
                :total-all-questions="totalAllQuestions"
                :durasi-menit="durasiDemoMenit"
                :ringkasan-section="ringkasanSection"
                :hasil-penilaian="hasilPenilaian"
                :sedang-menilai="sedangMenilai"
                :error-penilaian="errorPenilaian"
                :back-url="backUrl"
                @restart="ulangiDemo"
                @retry-score="hitungSkorDemo"
            />
        </div>

        <template #footer>
            <QuestionNavigation
                v-if="isQuestions && !demoSelesai"
                :current-number="globalNumber"
                :visible-numbers="visibleNumbers"
                :answered-numbers="answeredNumbers"
                :strip-anim="stripAnim"
                :first-number="navigationFirstNumber"
                :last-number="navigationLastNumber"
                :can-go-prev="canGoPrev()"
                :is-last-step="isLastStep()"
                @previous="goToPrev"
                @select-number="goToGlobal"
                @next="goToNext"
                @show-grid="showGridModal = true"
            />
        </template>
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
.exam-control-light {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 88%,
        transparent
    );
    background-color: var(--color-surface-white);
    color: var(--color-text-heading);
}

.exam-control-dark {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 88%,
        transparent
    );
    background-color: var(--color-surface-container-low);
    color: var(--color-text-heading);
}

.exam-header-timer-light {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 88%,
        transparent
    );
    background-color: var(--color-surface-container-low);
    color: var(--color-text-heading);
}

.exam-header-timer-dark {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 88%,
        transparent
    );
    background-color: var(--color-surface-container-low);
    color: var(--color-text-heading);
}

.exam-header-divider-light {
    background-color: var(--color-outline-variant);
}

.exam-header-divider-dark {
    background-color: var(--color-outline-variant);
}

.exam-section-nav {
    background-color: var(--color-canvas);
}

.exam-section-card {
    border-color: var(--color-outline-variant);
}

.dark .exam-section-card {
    border-color: color-mix(
        in srgb,
        var(--color-text-muted) 30%,
        transparent
    );
}

.exam-tabs-scroll {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 86%,
        transparent
    );
    background-color: var(--color-surface-container);
}

.exam-nav-divider {
    background-color: var(--color-outline-variant);
}

.exam-tab-active {
    background-color: var(--color-primary);
    color: var(--color-on-primary);
    box-shadow: 0 5px 14px color-mix(in srgb, var(--color-primary) 26%, transparent);
}

.exam-tab-inactive {
    background-color: transparent;
    color: var(--color-text-heading);
}

.exam-progress-track {
    background-color: var(--color-track-neutral);
}

.exam-progress-value {
    background-color: var(--color-primary);
}

.preview-question-panel {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 86%,
        transparent
    );
    background-color: var(--color-surface-white);
}

.exam-workspace-grid {
    grid-auto-rows: minmax(0, 1fr);
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
</style>
