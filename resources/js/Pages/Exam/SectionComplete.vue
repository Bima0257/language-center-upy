<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    session: { type: Object, default: null },
});
</script>

<template>
    <Head title="Section Selesai" />
    <div class="min-h-screen bg-[#FAFAFA] flex items-center justify-center p-6">
        <div class="bg-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center">
            <h1 class="text-headline-md font-bold text-primary mb-4">Section Selesai</h1>
            <p class="text-text-body text-body-md">Anda akan melanjutkan ke section berikutnya.</p>
            <button class="mt-6 w-full bg-primary-container text-white py-3.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                Lanjut ke Section Berikutnya
            </button>
        </div>
    </div>
</template>
