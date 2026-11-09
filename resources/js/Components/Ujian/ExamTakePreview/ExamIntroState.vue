<script setup>
import {
    IconArrowRight,
    IconInfoCircle,
} from "@tabler/icons-vue";

defineProps({
    overview: { type: Object, default: () => ({}) },
    participantName: { type: String, default: "Peserta" },
});

const emit = defineEmits(["next"]);
</script>

<template>
    <div
        class="w-full max-w-3xl overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-white shadow-standard dark:bg-surface-container"
    >
        <div class="flex items-center gap-2.5 bg-primary px-5 py-3.5 text-on-primary">
            <IconInfoCircle :size="18" class="shrink-0" />
            <div class="min-w-0">
                <span
                    class="block text-xs font-semibold uppercase tracking-widest opacity-70"
                    >Informasi Ujian</span
                >
                <h1 class="truncate text-title-lg font-bold leading-tight">
                    {{ overview.judulUjian || "Pratinjau Ujian" }}
                </h1>
            </div>
        </div>
        <div class="px-5 py-4">
            <div
                class="mb-4 grid grid-cols-3 divide-x divide-outline-variant/50 border-y border-outline-variant/40 py-2.5"
            >
                <div class="min-w-0 px-2 first:pl-0 last:pr-0">
                    <p class="mb-0.5 text-xs text-text-muted">Durasi</p>
                    <p class="text-base font-bold text-text-heading">
                        <template v-if="overview.durasiMenit">
                            {{ overview.durasiMenit }} menit
                        </template>
                        <template v-else>Tidak ditentukan</template>
                    </p>
                </div>
                <div class="min-w-0 px-2">
                    <p class="mb-0.5 text-xs text-text-muted">Total Soal</p>
                    <p class="text-base font-bold text-text-heading">
                        {{ overview.jumlahSoal }}
                    </p>
                </div>
                <div class="min-w-0 px-2 first:pl-0 last:pr-0">
                    <p class="mb-0.5 text-xs text-text-muted">Peserta</p>
                    <p
                        class="truncate text-base font-bold text-text-heading"
                    >
                        {{ participantName }}
                    </p>
                </div>
            </div>

            <p class="mb-4 text-sm leading-snug text-text-body">
                Bacalah petunjuk setiap bagian dengan teliti sebelum mengerjakan.
                Soal akan dimuat secara berurutan per keterampilan dan bagianSoal.
                Nomor soal berjalan menerus dari bagian pertama sampai bagian terakhir.
            </p>

            <div class="mb-4 divide-y divide-outline-variant/40">
                <div
                    v-for="keterampilan in overview.keterampilan || []"
                    :key="keterampilan.kode"
                    class="py-2.5 first:pt-0 last:pb-0"
                    >
                    <div class="flex items-center gap-2">
                        <span class="min-w-0 truncate text-sm font-bold text-text-heading">{{ keterampilan.nama }}</span>
                        <span class="ml-auto shrink-0 text-xs text-text-muted"
                            >{{ keterampilan.jumlahSoal }} soal</span
                        >
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-1 pt-1 text-xs text-text-muted">
                        <span
                            v-for="bagianSoal in keterampilan.bagianSoal"
                            :key="bagianSoal.nama"
                            >{{ bagianSoal.nama }} <span class="tabular-nums">({{ bagianSoal.jumlahSoal }} soal)</span></span
                        >
                    </div>
                </div>
            </div>

            <div class="border-t border-outline-variant/40 pt-3">
                <button
                    @click="emit('next')"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary shadow-md transition-all duration-150 hover:bg-primary/90 active:scale-[0.98]"
                >
                    Mulai <IconArrowRight :size="16" />
                </button>
            </div>
        </div>
    </div>
</template>
