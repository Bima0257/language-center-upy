<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    session: { type: Object, default: null },
});
</script>

<template>
    <Head title="Section Selesai" />
    <div class="min-h-screen bg-[#F1F2F6] flex items-center justify-center p-6">
        <div class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center">
            <h1 class="text-headline-md font-bold text-primary mb-4">Section Selesai</h1>
            <p class="text-text-body text-body-md">Anda akan melanjutkan ke section berikutnya.</p>
            <BaseButton size="lg" class="w-full mt-6">
                Lanjut ke Section Berikutnya
            </BaseButton>
        </div>
    </div>
</template>
