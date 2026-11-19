<script setup>
import { Link } from "@inertiajs/vue3";
import {
    IconArrowLeft,
    IconCircleCheck,
    IconPrinter,
    IconRefresh,
} from "@tabler/icons-vue";
import { computed } from "vue";
import CertificateScore from "@/Components/Ujian/CertificateScore.vue";

const props = defineProps({
    overview: { type: Object, default: () => ({}) },
    participantName: { type: String, default: "Pratinjau" },
    answeredCount: { type: Number, default: 0 },
    totalAllQuestions: { type: Number, default: 0 },
    durasiMenit: { type: Number, default: 0 },
    ringkasanSection: { type: Array, default: () => [] },
    hasilPenilaian: { type: Object, default: null },
    sedangMenilai: { type: Boolean, default: false },
    errorPenilaian: { type: String, default: null },
    backUrl: { type: String, default: null },
});

const emit = defineEmits(["restart", "retry-score"]);

const tanggalDemo = new Date();
const tahunDemo = tanggalDemo.getFullYear();

function formatTanggal(date) {
    return new Intl.DateTimeFormat("en-GB", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(date);
}

function cariSkorSection(kataKunci) {
    const baris = props.hasilPenilaian?.skor_section ?? [];
    const cocok =
        baris.find(
            (item) =>
                String(item.nama_section ?? "").toLowerCase() === kataKunci,
        ) ??
        baris.find((item) =>
            String(item.kode_section ?? "").toLowerCase().includes(kataKunci),
        ) ??
        baris.find((item) =>
            String(item.nama_section ?? "").toLowerCase().includes(kataKunci),
        );

    return cocok ? Number(cocok.skor_skala ?? 0) : null;
}

const sertifikatDemo = computed(() => {
    if (!props.hasilPenilaian) return null;

    const listening = cariSkorSection("listening");
    const reading = cariSkorSection("reading");

    if (listening === null || reading === null) return null;

    return {
        certificateNo: `DEMO/EPT/${tahunDemo}`,
        issueDate: formatTanggal(tanggalDemo),
        testTakerName: props.participantName || "Pratinjau",
        studentId: "DEMO",
        listeningScore: listening,
        readingScore: reading,
        totalScore: Number(props.hasilPenilaian.total ?? 0),
        cefrLevel: props.hasilPenilaian.tingkat_cefr || "-",
        cefrTitle: props.hasilPenilaian.label_tingkat || "Not available",
        cefrDescription: props.hasilPenilaian.deskripsi_tingkat || "-",
        testDate: formatTanggal(tanggalDemo),
        validUntil: formatTanggal(
            new Date(
                tanggalDemo.getFullYear() + 2,
                tanggalDemo.getMonth(),
                tanggalDemo.getDate(),
            ),
        ),
        headName: "Rudha Widagsa, ph.D",
        headNis: "1985 0102 2013101 001",
    };
});

function cetakSertifikat() {
    window.print();
}

const sectionRows = computed(() =>
    props.ringkasanSection.map((item) => {
        const skor =
            props.hasilPenilaian?.skor_section?.find(
                (baris) => baris.kode_section === item.kode,
            ) ??
            props.hasilPenilaian?.skor_section?.find(
                (baris) => baris.nama_section === item.nama,
            );

        return {
            kode: item.kode,
            nama: item.nama,
            terjawab: item.terjawab,
            total: item.total,
            benar: skor?.jumlah_benar ?? null,
            skor: skor?.skor_skala ?? null,
        };
    }),
);

function formatSkor(value) {
    const angka = Number(value);

    return Number.isFinite(angka) ? angka.toFixed(1) : "-";
}
</script>

<template>
    <div
        class="w-full max-w-2xl overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-white shadow-standard dark:bg-surface-container"
    >
        <div class="flex items-center gap-2.5 bg-primary px-4 py-3 text-on-primary">
            <IconCircleCheck :size="18" class="shrink-0" />
            <div class="min-w-0">
                <span
                    class="block text-xs font-semibold uppercase tracking-widest opacity-70"
                >
                    Demo Selesai
                </span>
                <h1 class="truncate text-title-lg font-bold leading-tight">
                    {{ overview.judulUjian || "Demo Ujian" }}
                </h1>
            </div>
        </div>

        <div class="px-4 py-3.5">
            <p class="mb-3 text-xs leading-snug text-text-body">
                Skor dihitung menggunakan aturan penilaian aktif. Jawaban demo
                tidak disimpan ke database.
            </p>

            <div
                class="mb-3 grid grid-cols-2 divide-x divide-outline-variant/50 border-y border-outline-variant/40 py-2"
            >
                <div class="min-w-0 px-2">
                    <p class="mb-0.5 text-[11px] text-text-muted">
                        Jawaban Terisi
                    </p>
                    <p class="text-sm font-bold text-text-heading">
                        {{ answeredCount }} / {{ totalAllQuestions }}
                    </p>
                </div>
                <div class="min-w-0 px-2">
                    <p class="mb-0.5 text-[11px] text-text-muted">
                        Durasi Demo
                    </p>
                    <p class="text-sm font-bold text-text-heading">
                        {{ durasiMenit }} menit
                    </p>
                </div>
            </div>

            <div
                v-if="sectionRows.length"
                class="mb-3 divide-y divide-outline-variant/40 border-b border-outline-variant/40"
            >
                <div
                    v-for="row in sectionRows"
                    :key="row.kode"
                    class="flex items-center gap-2 py-1.5"
                >
                    <span
                        class="min-w-0 truncate text-xs font-semibold text-text-heading"
                    >
                        {{ row.nama }}
                    </span>
                    <span
                        class="ml-auto shrink-0 text-right text-[11px] tabular-nums text-text-muted"
                    >
                        {{ row.terjawab }}/{{ row.total }} dijawab<span
                            v-if="row.benar !== null"
                        >
                            · {{ row.benar }} benar</span
                        >
                    </span>
                    <span
                        v-if="row.skor !== null"
                        class="w-12 shrink-0 text-right text-xs font-bold tabular-nums text-text-heading"
                    >
                        {{ formatSkor(row.skor) }}
                    </span>
                </div>
            </div>

            <div
                v-if="sedangMenilai"
                class="mb-3 rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2 text-xs text-text-muted"
            >
                Menghitung skor demo...
            </div>

            <div
                v-else-if="errorPenilaian"
                class="mb-3 rounded-xl border border-error-red/30 bg-error-container/30 px-3 py-2 text-xs text-error-red"
            >
                <p>{{ errorPenilaian }}</p>
                <button
                    type="button"
                    class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-error-red/30 bg-surface-white px-3 py-1.5 text-[11px] font-semibold text-error-red"
                    @click="emit('retry-score')"
                >
                    <IconRefresh :size="13" />
                    Coba Hitung Ulang
                </button>
            </div>

            <div
                v-else-if="hasilPenilaian"
                class="mb-3 rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5"
            >
                <div class="flex items-center gap-2">
                    <span class="text-xs text-text-muted">Total Skor</span>
                    <span
                        class="ml-auto text-lg font-bold tabular-nums text-text-heading"
                    >
                        {{ formatSkor(hasilPenilaian.total) }}/{{
                            formatSkor(hasilPenilaian.total_maksimum)
                        }}
                    </span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                    <span
                        v-if="hasilPenilaian.tingkat_cefr"
                        class="text-xs font-semibold text-text-heading"
                    >
                        {{ hasilPenilaian.tingkat_cefr }} —
                        {{ hasilPenilaian.label_tingkat }}
                    </span>
                    <span
                        class="ml-auto rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="
                            hasilPenilaian.lulus
                                ? 'bg-success-container text-on-success-container'
                                : 'bg-error-container text-on-error-container'
                        "
                    >
                        {{ hasilPenilaian.lulus ? "Lulus" : "Tidak Lulus" }}
                    </span>
                </div>
            </div>

            <div
                class="flex flex-wrap items-center justify-end gap-2 border-t border-outline-variant/40 pt-3"
            >
                <button
                    v-if="sertifikatDemo"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-primary px-3.5 py-2 text-xs font-semibold text-on-primary shadow-md transition-all duration-150 hover:bg-primary/90 active:scale-[0.98]"
                    @click="cetakSertifikat"
                >
                    <IconPrinter :size="14" />
                    Cetak Sertifikat
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-primary px-3.5 py-2 text-xs font-semibold text-on-primary shadow-md transition-all duration-150 hover:bg-primary/90 active:scale-[0.98]"
                    @click="emit('restart')"
                >
                    <IconRefresh :size="14" />
                    Ulangi Demo
                </button>
                <Link
                    v-if="backUrl"
                    :href="backUrl"
                    class="inline-flex items-center gap-1.5 rounded-full border border-outline-variant bg-surface-white px-3.5 py-2 text-xs font-semibold text-text-heading transition-all duration-150 hover:bg-surface-container-low active:scale-[0.98]"
                >
                    <IconArrowLeft :size="14" />
                    Kembali ke Daftar Demo
                </Link>
            </div>
        </div>
    </div>

    <div v-if="sertifikatDemo" class="cert-print-region" aria-hidden="true">
        <CertificateScore v-bind="sertifikatDemo" />
    </div>
</template>

<style scoped>
.cert-print-region {
    display: none;
}

@media print {
    :global(html:has(.cert-print-region)),
    :global(body:has(.cert-print-region)) {
        margin: 0 !important;
        padding: 0 !important;
        background-color: #ffffff !important;
    }

    :global(body:has(.cert-print-region) *) {
        visibility: hidden !important;
    }

    .cert-print-region,
    .cert-print-region :deep(*) {
        visibility: visible !important;
    }

    .cert-print-region {
        display: block !important;
        position: fixed;
        top: 50%;
        left: 50%;
        width: 100%;
        height: auto;
        margin: 0 !important;
        padding: 0 !important;
        page: certificate;
        break-inside: avoid;
        transform: translate(-50%, -50%);
    }

    :global(body:has(.cert-print-region) .min-h-screen) {
        display: block !important;
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
    }

    :global(body:has(.cert-print-region) main) {
        display: block !important;
        height: 0 !important;
        overflow: hidden !important;
        padding-top: 0 !important;
    }

    :global(body:has(.cert-print-region) main > header) {
        display: none !important;
    }
}
</style>
