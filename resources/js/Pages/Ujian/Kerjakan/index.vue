<script setup>
import { Head } from "@inertiajs/vue3";
import TataLetakUjian from "@/Layouts/TataLetakUjian.vue";
import GridSoalModal from "@/Components/Ujian/GridSoalModal.vue";
import ModalPelanggaran from "@/Pages/Ujian/Kerjakan/components/ModalPelanggaran.vue";
import AudioMaterialPanel from "@/Pages/Ujian/Kerjakan/components/AudioMaterialPanel.vue";
import QuestionNavigation from "@/Pages/Ujian/Kerjakan/components/QuestionNavigation.vue";
import QuestionPresentation from "@/Pages/Ujian/Kerjakan/components/QuestionPresentation.vue";
import ReadingMaterialPanel from "@/Pages/Ujian/Kerjakan/components/ReadingMaterialPanel.vue";
import SubmitConfirmationModal from "@/Pages/Ujian/Kerjakan/components/SubmitConfirmationModal.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { usePengaturWaktuUjian } from "@/Modules/UjianModule/Composables/usePengaturWaktuUjian.js";
import { useKeamananUjian } from "@/Modules/UjianModule/Composables/useKeamananUjian.js";
import { useSimpanOtomatis } from "@/Modules/UjianModule/Composables/useSimpanOtomatis.js";
import axios from "axios";

const props = defineProps({
    sesiUjian: { type: Object, required: true },
    soalBank: { type: Array, default: () => [] },
});

const bagianAktif = computed(() => props.sesiUjian.bagian_aktif);

const currentQuestions = computed(() => {
    const keterampilanId = bagianAktif.value?.keterampilan_id;
    if (!keterampilanId) return props.soalBank;
    return props.soalBank
        .filter((q) => q.keterampilan_id === keterampilanId)
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0));
});

const jumlahSoal = computed(() => currentQuestions.value.length);

const answers = ref({});

const sessionAnswers = props.sesiUjian.jawaban || [];

for (const a of sessionAnswers) {
    answers.value[a.soal_id] = a.teks_jawaban;
}

const answeredCount = computed(() => Object.keys(answers.value).length);

// ============================================================
// FOOTER NUMBER STRIP (per bagianUjian) — sliding window maks 5 nomor
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

const mapGroups = computed(() => {
    if (!currentQuestions.value.length) return [];
    const keterampilanName =
        currentQuestions.value[0]?.keterampilan?.nama ||
        bagianSoalLabel.value ||
        "Bagian Ujian";
    return [
        {
            keterampilan: { nama: keterampilanName },
            bagianSoal: { nama: bagianSoalLabel.value || "Soal" },
            nomorMulai: 1,
            jumlahSoal: currentQuestions.value.length,
            soal: currentQuestions.value.map((q, i) => ({
                id: q.id,
                nomor_global: i + 1,
            })),
        },
    ];
});

const elapsedSeconds = props.sesiUjian.dimulai_pada
    ? Math.floor(
          (Date.now() - new Date(props.sesiUjian.dimulai_pada).getTime()) / 1000,
      )
    : 0;

const totalSeconds = computed(
    () => (props.sesiUjian.slot_ujian?.periode_ujian?.ujian?.durasi_menit || 35) * 60,
);

const {
    remaining,
    minutes,
    seconds,
    isWarning,
    isDanger,
    start: startTimer,
} = usePengaturWaktuUjian({
    totalSeconds: totalSeconds.value,
    elapsedSeconds,
    onExpire: () => handleTimeUp(),
});

const {
    violations,
    strikeCount,
    activate: activateSecurity,
    deactivate: deactivateSecurity,
} = useKeamananUjian({
    sesiUjianId: props.sesiUjian.id,
    heartbeatInterval: 30000,
});

const { lastSaved, isSaving, save: autoSave } = useSimpanOtomatis({
    sesiUjianId: props.sesiUjian.id,
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

const isMaterialAudio = computed(
    () => currentQuestion.value?.materi === "audio",
);
const materiSoal = computed(() => currentQuestion.value?.materi_soal || null);
const bagianSoalLabel = computed(
    () =>
        currentQuestion.value?.bagian_soal?.nama ||
        currentQuestion.value?.keterampilan?.nama ||
        "",
);

const headerTitle = computed(() => {
    const prefix = [
        currentQuestion.value?.keterampilan?.nama,
        currentQuestion.value?.bagian_soal?.nama,
    ]
        .filter(Boolean)
        .join(' ');
    return (
        (prefix ? prefix + ' — ' : '') +
        'Soal ' +
        (currentQuestionIndex.value + 1) +
        ' dari ' +
        jumlahSoal.value
    );
});

const audioSrc = computed(() => {
    const path = materiSoal.value?.url_audio;
    return path ? "/media/" + path : null;
});

const imageSrc = computed(() => {
    const path = materiSoal.value?.url_gambar;
    return path ? "/media/" + path : null;
});

const imageLoading = useMediaLoad(imageSrc);

function goToQuestion(index) {
    if (index >= 0 && index < jumlahSoal.value) {
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
        .post(`/ujian/sesi/${props.sesiUjian.id}/selesaikan-bagian`)
        .catch(() => {});
}

function submitExam() {
    axios
        .post(`/ujian/sesi/${props.sesiUjian.id}/serahkan`)
        .then(() => {
            window.location.href = "/dasbor";
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
    <TataLetakUjian
        :sesi-ujian="sesiUjian"
        :title="headerTitle"
        :remaining-seconds="remaining"
        :minutes="minutes"
        :seconds="seconds"
        :is-warning="isWarning"
        :is-danger="isDanger"
    >
        <div class="flex h-full">
            <ReadingMaterialPanel
                v-if="!isMaterialAudio"
                :materi-soal="materiSoal"
                :bagian-soal-label="bagianSoalLabel"
                :image-loading="imageLoading"
            />
            <AudioMaterialPanel
                v-else
                :materi-soal="materiSoal"
                :bagian-soal-label="bagianSoalLabel"
                :image-src="imageSrc"
                :audio-src="audioSrc"
                :image-loading="imageLoading"
            />

            <section class="w-2/5 flex flex-col bg-surface-white">
                <QuestionPresentation
                    :current-question="currentQuestion"
                    :current-question-index="currentQuestionIndex"
                    :jumlah-soal="jumlahSoal"
                    :answered-count="answeredCount"
                    :strike-count="strikeCount"
                    :is-saving="isSaving"
                    :last-saved="lastSaved"
                    :answers="answers"
                    :is-audio="isMaterialAudio"
                    @select-answer="selectAnswer"
                />
                <QuestionNavigation
                    :current-question-index="currentQuestionIndex"
                    :jumlah-soal="jumlahSoal"
                    :current-questions="currentQuestions"
                    :answers="answers"
                    :visible-numbers="visibleNumbers"
                    :strip-anim="stripAnim"
                    @navigate="goToQuestion"
                    @submit="showSubmitConfirm = true"
                    @show-grid="showGridModal = true"
                />
            </section>
        </div>
    </TataLetakUjian>

    <SubmitConfirmationModal
        :show="showSubmitConfirm"
        :answered-count="answeredCount"
        :jumlah-soal="jumlahSoal"
        @close="showSubmitConfirm = false"
        @submit="submitExam"
    />

    <ModalPelanggaran
        :show="showViolationModal"
        :message="currentViolation?.type?.replace(/_/g, ' ') || ''"
        :strike="strikeCount"
        @back="backToExam"
        @close="backToExam"
    />

    <GridSoalModal
        :show="showGridModal"
        :groups="mapGroups"
        :answers="answers"
        :current-global-number="currentQuestionIndex + 1"
        @navigate-global="goToQuestion"
        @close="showGridModal = false"
    />
</template>
