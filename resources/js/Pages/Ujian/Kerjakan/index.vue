<script setup>
import { Head } from "@inertiajs/vue3";
import TataLetakUjian from "@/Layouts/TataLetakUjian.vue";
import GridSoalModal from "@/Components/Ujian/GridSoalModal.vue";
import ModalPelanggaran from "@/Pages/Ujian/Kerjakan/Components/ModalPelanggaran.vue";
import AudioMaterialPanel from "@/Pages/Ujian/Kerjakan/Components/AudioMaterialPanel.vue";
import QuestionNavigation from "@/Pages/Ujian/Kerjakan/Components/QuestionNavigation.vue";
import QuestionPresentation from "@/Pages/Ujian/Kerjakan/Components/QuestionPresentation.vue";
import ReadingMaterialPanel from "@/Pages/Ujian/Kerjakan/Components/ReadingMaterialPanel.vue";
import SubmitConfirmationModal from "@/Pages/Ujian/Kerjakan/Components/SubmitConfirmationModal.vue";
import { useMediaLoad } from "@/Composables/useMediaLoad";
import { useToast } from "@/Composables/useToast";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { usePengaturWaktuUjian } from "@/Modules/UjianModule/Composables/usePengaturWaktuUjian.js";
import { useKeamananUjian } from "@/Modules/UjianModule/Composables/useKeamananUjian.js";
import { useSimpanOtomatis } from "@/Modules/UjianModule/Composables/useSimpanOtomatis.js";
import axios from "axios";

const props = defineProps({
    sesiUjian: { type: Object, required: true },
    soalBank: { type: Array, default: () => [] },
    waktu: { type: Object, default: () => ({}) },
});

const toast = useToast();

const partUjianAktif = computed(() => props.sesiUjian.part_ujian_aktif);

const currentQuestions = computed(() => {
    const sectionId = partUjianAktif.value?.section_id;
    if (!sectionId) return props.soalBank;
    return props.soalBank
        .filter((q) => q.section_id === sectionId)
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0));
});

const jumlahSoal = computed(() => currentQuestions.value.length);

const answers = ref({});

const sessionAnswers = props.sesiUjian.jawaban || [];

for (const a of sessionAnswers) {
    answers.value[a.soal_id] = a.teks_jawaban;
}

const answeredInSection = computed(() =>
    currentQuestions.value.filter((q) => {
        const nilai = answers.value[q.id];
        return nilai !== undefined && nilai !== null && String(nilai).trim() !== "";
    }).length,
);

const semuaTerjawab = computed(
    () => jumlahSoal.value > 0 && answeredInSection.value >= jumlahSoal.value,
);

const sectionTerakhir = computed(() => Boolean(props.waktu?.section?.terakhir));

const currentQuestionIndex = ref(0);

// ============================================================
// FOOTER NUMBER STRIP (per partUjian) — sliding window maks 5 nomor
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
    const sectionName =
        currentQuestions.value[0]?.section?.nama ||
        partSoalLabel.value ||
        "Part Ujian";
    return [
        {
            section: { nama: sectionName },
            partSoal: { nama: partSoalLabel.value || "Soal" },
            nomorMulai: 1,
            jumlahSoal: currentQuestions.value.length,
            soal: currentQuestions.value.map((q, i) => ({
                id: q.id,
                nomor_global: i + 1,
            })),
        },
    ];
});

// ============================================================
// TIMER SECTION + TOTAL
// ============================================================

const sedangProsesWaktu = ref(false);
const sedangSubmit = ref(false);

const sectionTimer = usePengaturWaktuUjian({
    totalSeconds: Math.max(0, Number(props.waktu?.section?.sisa_detik ?? 0)),
    elapsedSeconds: 0,
    onExpire: () => selesaikanKarenaWaktuSection(),
});

const totalTimer = usePengaturWaktuUjian({
    totalSeconds: Math.max(0, Number(props.waktu?.total?.sisa_detik ?? 0)),
    elapsedSeconds: 0,
    onExpire: () => submitExam(),
});

const {
    remaining: sectionRemaining,
    minutes: sectionMinutes,
    seconds: sectionSeconds,
    isWarning: sectionIsWarning,
    isDanger: sectionIsDanger,
    start: startSectionTimer,
    sync: syncSectionTimer,
} = sectionTimer;

const {
    remaining: totalRemaining,
    minutes: totalMinutes,
    seconds: totalSeconds,
    isWarning: totalIsWarning,
    isDanger: totalIsDanger,
    start: startTotalTimer,
    sync: syncTotalTimer,
} = totalTimer;

const { lastSaved, isSaving, save: autoSave, flush } = useSimpanOtomatis({
    sesiUjianId: props.sesiUjian.id,
    debounceMs: 500,
});

const {
    violations,
    strikeCount,
    activate: activateSecurity,
    deactivate: deactivateSecurity,
} = useKeamananUjian({
    sesiUjianId: props.sesiUjian.id,
    heartbeatInterval: 30000,
    onHeartbeat: (data) => tanganiHasilWaktu(data),
});

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
const partSoalLabel = computed(
    () =>
        currentQuestion.value?.part_soal?.nama ||
        currentQuestion.value?.section?.nama ||
        "",
);

const headerTitle = computed(() => {
    const prefix = [
        currentQuestion.value?.section?.nama,
        currentQuestion.value?.part_soal?.nama,
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

function sinkronTimer(data) {
    const sisaSection = data?.waktu?.section?.sisa_detik;
    const sisaTotal = data?.waktu?.total?.sisa_detik;

    if (sisaSection !== null && sisaSection !== undefined) {
        syncSectionTimer(Math.max(0, Number(sisaSection)));
        startSectionTimer();
    }

    if (sisaTotal !== null && sisaTotal !== undefined) {
        syncTotalTimer(Math.max(0, Number(sisaTotal)));
        startTotalTimer();
    }
}

function tanganiHasilWaktu(data) {
    if (!data) return;

    if (data.diserahkan) {
        window.location.href = data.redirect || "/dasbor";
        return;
    }

    if (!data.sesi_aktif) {
        window.location.href = "/dasbor";
        return;
    }

    const partAktifId = partUjianAktif.value?.id ?? props.sesiUjian.part_ujian_aktif_id;

    if (
        data.part_ujian_aktif_id &&
        String(data.part_ujian_aktif_id) !== String(partAktifId)
    ) {
        window.location.reload();
        return;
    }

    sinkronTimer(data);
}

function selesaikanKarenaWaktuSection() {
    if (sedangProsesWaktu.value) return;
    sedangProsesWaktu.value = true;

    axios
        .post(`/ujian/sesi/${props.sesiUjian.id}/selesaikan-part`)
        .then(({ data }) => tanganiHasilWaktu(data))
        .catch(() => {})
        .finally(() => {
            sedangProsesWaktu.value = false;
        });
}

async function selesaikanSectionManual() {
    if (!semuaTerjawab.value || sectionTerakhir.value || sedangProsesWaktu.value) {
        return;
    }

    const partId = partUjianAktif.value?.id;
    if (!partId) return;

    sedangProsesWaktu.value = true;

    try {
        await flush();

        const { data } = await axios.post(
            `/ujian/sesi/${props.sesiUjian.id}/selesaikan-section`,
            { part_ujian_id: partId },
        );

        tanganiHasilWaktu(data);
    } catch (error) {
        const pesan =
            error?.response?.data?.errors?.section?.[0] ||
            error?.response?.data?.message ||
            "Gagal menyelesaikan section.";
        toast.error(pesan);
    } finally {
        sedangProsesWaktu.value = false;
    }
}

async function submitExam() {
    if (sedangSubmit.value) return;
    sedangSubmit.value = true;

    try {
        await flush();
        await axios.post(`/ujian/sesi/${props.sesiUjian.id}/serahkan`);
        window.location.href = "/dasbor";
    } catch {
        sedangSubmit.value = false;
    }
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
    startSectionTimer();
    startTotalTimer();
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
        :remaining-seconds="sectionRemaining"
        :minutes="sectionMinutes"
        :seconds="sectionSeconds"
        :is-warning="sectionIsWarning"
        :is-danger="sectionIsDanger"
        :total-remaining-seconds="totalRemaining"
        :total-minutes="totalMinutes"
        :total-seconds="totalSeconds"
        :show-total-timer="true"
        :total-is-warning="totalIsWarning"
        :total-is-danger="totalIsDanger"
    >
        <div class="flex h-full">
            <ReadingMaterialPanel
                v-if="!isMaterialAudio"
                :materi-soal="materiSoal"
                :part-soal-label="partSoalLabel"
                :image-loading="imageLoading"
            />
            <AudioMaterialPanel
                v-else
                :materi-soal="materiSoal"
                :part-soal-label="partSoalLabel"
                :image-src="imageSrc"
                :audio-src="audioSrc"
                :image-loading="imageLoading"
            />

            <section class="w-2/5 flex flex-col bg-surface-white">
                <QuestionPresentation
                    :current-question="currentQuestion"
                    :current-question-index="currentQuestionIndex"
                    :jumlah-soal="jumlahSoal"
                    :answered-count="answeredInSection"
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
                    :is-last-section="sectionTerakhir"
                    :all-answered="semuaTerjawab"
                    :is-finishing="sedangProsesWaktu"
                    @navigate="goToQuestion"
                    @submit="showSubmitConfirm = true"
                    @finish-section="selesaikanSectionManual"
                    @show-grid="showGridModal = true"
                />
            </section>
        </div>
    </TataLetakUjian>

    <SubmitConfirmationModal
        :show="showSubmitConfirm"
        :answered-count="answeredInSection"
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
