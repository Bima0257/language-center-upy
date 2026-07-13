<script setup>
import { Head } from '@inertiajs/vue3';
import { IconAlertTriangle } from '@tabler/icons-vue';

defineProps({
    session: { type: Object, default: null },
});
</script>

<template>
    <Head title="Konfirmasi" />
    <div class="min-h-screen bg-[#FAFAFA] flex items-center justify-center p-6">
        <div class="bg-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center">
            <IconAlertTriangle class="mx-auto text-amber-500 mb-4" :size="48" stroke="1.5" />
            <h1 class="text-headline-md font-bold text-primary mb-2">Yakin ingin mengumpulkan?</h1>
            <p class="text-text-body text-body-md mb-6">Jawaban yang sudah dikumpulkan tidak bisa diubah. Pastikan semua soal sudah terjawab.</p>
            <div class="flex gap-4">
                <button class="flex-1 border border-outline-variant text-primary py-3.5 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all">
                    Kembali
                </button>
                <button class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                    Kumpulkan
                </button>
            </div>
        </div>
    </div>
</template>
