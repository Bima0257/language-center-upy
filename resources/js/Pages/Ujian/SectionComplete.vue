<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    sesiUjian: { type: Object, default: null },
});
</script>

<template>
    <Head title="Bagian Ujian Selesai" />
    <div class="min-h-screen bg-canvas flex items-center justify-center p-6">
        <div class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center">
            <h1 class="text-headline-md font-bold text-primary mb-4">Bagian Ujian Selesai</h1>
            <p class="text-text-body text-body-md">Anda akan melanjutkan ke bagian berikutnya.</p>
            <BaseButton size="lg" class="w-full mt-6">
                Lanjut ke Bagian Ujian Berikutnya
            </BaseButton>
        </div>
    </div>
</template>
