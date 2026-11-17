<script setup>
import { Link } from "@inertiajs/vue3";
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import {
    IconArrowLeft,
    IconCircleCheck,
    IconRefresh,
} from "@tabler/icons-vue";

defineProps({
    overview: { type: Object, default: () => ({}) },
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

function formatSkor(value) {
    const angka = Number(value);

    return Number.isFinite(angka) ? angka.toFixed(1) : "-";
}
</script>

<template>
    <div
        class="w-full max-w-2xl overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-white shadow-standard dark:bg-surface-container"
    >
        <div class="flex items-center gap-2.5 bg-primary px-5 py-3.5 text-on-primary">
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

        <div class="px-5 py-4">
            <p class="mb-4 text-sm leading-snug text-text-body">
                Skor dihitung menggunakan aturan penilaian aktif. Jawaban demo
                tidak disimpan ke database.
            </p>

            <div
                class="mb-4 grid grid-cols-2 divide-x divide-outline-variant/50 border-y border-outline-variant/40 py-2.5"
            >
                <div class="min-w-0 px-2">
                    <p class="mb-0.5 text-xs text-text-muted">
                        Jawaban Terisi
                    </p>
                    <p class="text-base font-bold text-text-heading">
                        {{ answeredCount }} / {{ totalAllQuestions }}
                    </p>
                </div>
                <div class="min-w-0 px-2">
                    <p class="mb-0.5 text-xs text-text-muted">Durasi Demo</p>
                    <p class="text-base font-bold text-text-heading">
                        {{ durasiMenit }} menit
                    </p>
                </div>
            </div>

            <div
                v-if="sedangMenilai"
                class="mb-4 rounded-xl border border-outline-variant/50 bg-surface-container-low px-4 py-3 text-sm text-text-muted"
            >
                Menghitung skor demo...
            </div>

            <div
                v-else-if="errorPenilaian"
                class="mb-4 rounded-xl border border-error-red/30 bg-error-container/30 px-4 py-3 text-sm text-error-red"
            >
                <p>{{ errorPenilaian }}</p>
                <button
                    type="button"
                    class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-error-red/30 bg-surface-white px-3 py-1.5 text-xs font-semibold text-error-red"
                    @click="emit('retry-score')"
                >
                    <IconRefresh :size="14" />
                    Coba Hitung Ulang
                </button>
            </div>

            <div
                v-else-if="hasilPenilaian"
                class="mb-4 rounded-xl border border-outline-variant/50 bg-surface-container-low px-4 py-3"
            >
                <div class="space-y-2">
                    <div
                        v-for="skor in hasilPenilaian.skor_section"
                        :key="skor.section_id"
                        class="flex items-center gap-2"
                    >
                        <span class="min-w-0 truncate text-sm font-semibold text-text-heading">
                            {{ skor.nama_section }}
                        </span>
                        <span class="ml-auto shrink-0 text-xs text-text-muted">
                            {{ skor.jumlah_benar }}/{{ skor.jumlah_soal }} benar
                        </span>
                        <span class="w-14 shrink-0 text-right text-sm font-bold text-text-heading">
                            {{ formatSkor(skor.skor_skala) }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 border-t border-outline-variant/50 pt-3 text-center">
                    <p class="text-xs text-text-muted">Total Skor</p>
                    <p class="text-title-lg font-bold text-text-heading">
                        {{ formatSkor(hasilPenilaian.total) }}/{{ formatSkor(hasilPenilaian.total_maksimum) }}
                    </p>
                    <p v-if="hasilPenilaian.tingkat_cefr" class="mt-1 text-sm font-semibold text-text-heading">
                        {{ hasilPenilaian.tingkat_cefr }} — {{ hasilPenilaian.label_tingkat }}
                    </p>
                    <RichTextViewer
                        v-if="hasilPenilaian.deskripsi_tingkat"
                        :content="hasilPenilaian.deskripsi_tingkat"
                        class="mt-1 text-xs"
                    />
                    <p
                        class="mt-1 text-sm font-bold"
                        :class="hasilPenilaian.lulus ? 'text-success' : 'text-error-red'"
                    >
                        {{ hasilPenilaian.lulus ? "Lulus" : "Tidak Lulus" }}
                    </p>
                </div>
            </div>

            <div
                v-if="ringkasanSection.length"
                class="mb-5 space-y-2"
            >
                <div
                    v-for="item in ringkasanSection"
                    :key="item.kode"
                    class="flex items-center gap-2"
                >
                    <span
                        class="min-w-0 truncate text-sm font-semibold text-text-heading"
                    >
                        {{ item.nama }}
                    </span>
                    <span class="ml-auto shrink-0 text-xs text-text-muted">
                        {{ item.terjawab }}/{{ item.total }} soal
                    </span>
                </div>
            </div>

            <div
                class="flex flex-col gap-2 border-t border-outline-variant/40 pt-3 sm:flex-row"
            >
                <button
                    type="button"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary shadow-md transition-all duration-150 hover:bg-primary/90 active:scale-[0.98]"
                    @click="emit('restart')"
                >
                    <IconRefresh :size="16" />
                    Ulangi Demo
                </button>
                <Link
                    v-if="backUrl"
                    :href="backUrl"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-outline-variant px-4 py-2.5 text-sm font-semibold text-text-heading transition-all duration-150 hover:bg-surface-container-low active:scale-[0.98]"
                >
                    <IconArrowLeft :size="16" />
                    Kembali ke Daftar Demo
                </Link>
            </div>
        </div>
    </div>
</template>
