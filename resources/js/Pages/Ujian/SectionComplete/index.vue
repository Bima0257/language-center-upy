<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    sesiUjian: { type: Object, default: null },
});
</script>

<template>
    <Head title="Part Ujian Selesai" />
    <div class="min-h-screen bg-canvas flex items-center justify-center p-6">
        <div class="bg-surface-white rounded-2xl p-8 shadow-app-frame max-w-md w-full text-center">
            <h1 class="text-headline-md font-bold text-text-heading mb-4">Part Ujian Selesai</h1>
            <p class="text-text-body text-body-md">Anda akan melanjutkan ke part berikutnya.</p>
            <BaseButton size="lg" class="w-full mt-6">
                Lanjut ke Part Ujian Berikutnya
            </BaseButton>
        </div>
    </div>
</template>
