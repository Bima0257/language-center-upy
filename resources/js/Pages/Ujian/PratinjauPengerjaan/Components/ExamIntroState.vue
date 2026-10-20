<script setup>
import {
    IconArrowRight,
    IconBook,
    IconHeadphones,
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
        class="bg-surface-white rounded-3xl shadow-standard border border-outline-variant/30 max-w-3xl w-full overflow-hidden"
    >
        <div class="bg-secondary px-8 py-7 text-on-secondary">
            <div class="flex items-center gap-3 mb-2">
                <IconInfoCircle :size="22" />
                <span
                    class="text-label-md uppercase tracking-widest opacity-70 font-semibold"
                    >Informasi Ujian</span
                >
            </div>
            <h1 class="text-headline-md font-bold">
                {{ overview.judulUjian || "Pratinjau Ujian" }}
            </h1>
        </div>
        <div class="px-8 py-6">
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted mb-1">Durasi</p>
                    <p class="text-title-lg font-bold text-text-heading">
                        {{ overview.durasiMenit }} menit
                    </p>
                </div>
                <div class="rounded-xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted mb-1">Total Soal</p>
                    <p class="text-title-lg font-bold text-text-heading">
                        {{ overview.jumlahSoal }}
                    </p>
                </div>
                <div class="rounded-xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted mb-1">Peserta</p>
                    <p
                        class="text-title-lg font-bold text-text-heading truncate"
                    >
                        {{ participantName }}
                    </p>
                </div>
            </div>

            <p class="text-body-md text-text-body mb-5 leading-relaxed">
                Bacalah petunjuk setiap bagian dengan teliti sebelum mengerjakan.
                Soal akan dimuat secara berurutan per keterampilan dan bagianSoal.
                Nomor soal berjalan menerus dari bagian pertama sampai bagian terakhir.
            </p>

            <div class="space-y-3 mb-2">
                <div
                    v-for="keterampilan in overview.keterampilan || []"
                    :key="keterampilan.kode"
                    class="rounded-2xl border border-outline-variant/40 p-4"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <IconBook
                            v-if="keterampilan.materi === 'text'"
                            :size="18"
                            class="text-secondary"
                        />
                        <IconHeadphones
                            v-else
                            :size="18"
                            class="text-secondary"
                        />
                        <span class="font-bold text-text-heading">{{ keterampilan.nama }}</span>
                        <span class="ml-auto text-label-md text-text-muted"
                            >{{ keterampilan.jumlahSoal }} soal</span
                        >
                    </div>
                    <div class="flex flex-wrap gap-2 pl-7">
                        <span
                            v-for="bagianSoal in keterampilan.bagianSoal"
                            :key="bagianSoal.nama"
                            class="px-3 py-1 rounded-full bg-pastel-blue/60 text-primary text-[12px] font-semibold"
                            >{{ bagianSoal.nama }} · {{ bagianSoal.jumlahSoal }} soal</span
                        >
                    </div>
                </div>
            </div>

            <button
                @click="emit('next')"
                class="mt-6 w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-full text-label-md font-semibold bg-secondary text-on-secondary hover:bg-secondary/90 transition-all shadow-md active:scale-[0.98] duration-150"
            >
                Mulai <IconArrowRight :size="16" />
            </button>
        </div>
    </div>
</template>
