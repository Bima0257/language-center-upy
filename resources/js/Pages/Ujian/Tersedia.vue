<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { IconClipboardCheck } from '@tabler/icons-vue';

defineProps({
    slotUjian: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Ujian Tersedia" />
    <TataLetakDasbor title="Ujian Tersedia" subtitle="Daftar tryout yang dapat Anda ikuti">
        <div v-if="slotUjian.length === 0" class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-primary mb-2">Tidak Ada Sesi Tersedia</h2>
            <p class="text-text-body text-body-md">Saat ini belum ada sesi ujian yang dapat diikuti.</p>
        </div>
        <div v-else class="space-y-4">
            <BaseCard
                v-for="slotUjian in slotUjian"
                :key="slotUjian.id"
                class="flex items-center justify-between"
            >
                <div>
                    <h3 class="text-title-lg font-semibold text-primary">{{ slotUjian.periode_ujian?.ujian?.judul }}</h3>
                    <p class="text-text-muted text-label-md">
                        {{ slotUjian.periode_ujian?.judul }} — Sesi {{ slotUjian.waktu_mulai?.substring(0, 5) }}–{{ slotUjian.waktu_selesai?.substring(0, 5) }} WIB ({{ slotUjian.periode_ujian?.ujian?.durasi_menit }} menit)
                    </p>
                    <p class="text-text-muted text-xs mt-1">{{ slotUjian.kapasitas_peserta }} kuota — toleransi {{ slotUjian.toleransi_keterlambatan_menit ?? 15 }} menit</p>
                </div>
                <BaseButton :href="route('ujian.pemeriksaan-awal', slotUjian.id)">
                    <IconClipboardCheck :size="18" /> Ikuti Ujian
                </BaseButton>
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
