<script setup>
defineProps({
    slotUjian: { type: Object, required: true },
    lateDeadline: { type: String, required: true },
});
</script>

<template>
    <div>
        <h2 class="text-headline-md font-bold text-primary mb-2">{{ slotUjian.periode_ujian?.ujian?.judul }}</h2>
        <p class="text-text-body text-body-md">{{ slotUjian.periode_ujian?.judul }} — Sesi {{ slotUjian.waktu_mulai?.substring(0, 5) }}–{{ slotUjian.waktu_selesai?.substring(0, 5) }} WIB</p>
        <p class="text-warning text-xs mt-1">
            Mulai ujian harus dilakukan sebelum pukul {{ lateDeadline }} WIB (toleransi {{ slotUjian.toleransi_keterlambatan_menit ?? 15 }} menit).
        </p>
    </div>
</template>
