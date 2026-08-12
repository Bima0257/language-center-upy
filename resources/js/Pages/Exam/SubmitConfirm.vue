<script setup>
import { Head } from '@inertiajs/vue3';
import { IconAlertTriangle } from '@tabler/icons-vue';

defineProps({
    session: { type: Object, default: null },
});
</script>

<template>
    <Head title="Konfirmasi" />
    <div class="min-h-screen bg-[#F1F2F6] flex items-center justify-center p-6">
        <div class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center">
            <IconAlertTriangle class="mx-auto text-amber-500 dark:text-amber-400 mb-4" :size="48" stroke="1.5" />
            <h1 class="text-headline-md font-bold text-primary mb-2">Yakin ingin mengumpulkan?</h1>
            <p class="text-text-body text-body-md mb-6">Jawaban yang sudah dikumpulkan tidak bisa diubah. Pastikan semua soal sudah terjawab.</p>
            <div class="flex gap-4">
                <BaseButton variant="secondary" size="lg" class="flex-1">
                    Kembali
                </BaseButton>
                <BaseButton size="lg" class="flex-1">
                    Kumpulkan
                </BaseButton>
            </div>
        </div>
    </div>
</template>
