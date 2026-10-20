<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    sesiUjian: { type: Object, required: true },
});

function formatDuration(startedAt) {
    if (!startedAt) return '-';
    const minutes = Math.floor((Date.now() - new Date(startedAt).getTime()) / 60000);
    return `${minutes}m`;
}
</script>

<template>
    <Link :href="route('pengawas.sesi.detail', sesiUjian.id)"
          class="block bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 hover:border-secondary hover:shadow-md transition-all active:scale-[0.98]">
        <div class="flex items-start justify-between mb-3">
            <div class="min-w-0 flex-1">
                <p class="text-title-lg font-semibold text-primary truncate">{{ sesiUjian.pengguna?.name }}</p>
                <p class="text-label-md text-text-muted truncate">{{ sesiUjian.pengguna?.nim || 'NIM: -' }}</p>
            </div>
            <span class="flex items-center gap-1.5 text-label-md font-medium text-success shrink-0">
                LIVE → <span class="w-2 h-2 rounded-full bg-success inline-block animate-pulse"></span>
                AKTIF
            </span>
        </div>
        <p class="text-body-md text-text-body truncate mb-3">{{ sesiUjian.slot_ujian?.periode_ujian?.ujian?.judul }}</p>
        <div class="flex items-center gap-3 text-label-md text-text-muted">
            <span>⏱ {{ formatDuration(sesiUjian.dimulai_pada) }}</span>
            <span class="w-px h-3 bg-outline-variant"></span>
            <span :class="(sesiUjian.jumlah_pelanggaran || 0) > 0 ? 'text-warning font-medium' : ''">
                ⚠ {{ sesiUjian.jumlah_pelanggaran || 0 }} pelanggaran
            </span>
            <span class="w-px h-3 bg-outline-variant"></span>
            <span>{{ sesiUjian.jawaban_count || sesiUjian.jawaban?.length || 0 }} terjawab</span>
        </div>
    </Link>
</template>
