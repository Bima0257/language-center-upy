<script setup>
import { IconCheck, IconRefresh } from '@tabler/icons-vue';

const emit = defineEmits(['upload-again']);
</script>

<template>
    <div class="bg-warning-container border border-warning rounded-2xl p-8 text-center">
        <IconCheck class="mx-auto text-success mb-4" :size="48" stroke="1.5" />
        <h1 class="text-headline-md font-bold text-text-heading mb-2">Identitas Terkirim</h1>
        <p class="text-text-body text-body-md mb-6">
            Foto identitas Anda sudah diunggah dan menunggu verifikasi oleh admin.
            Proses verifikasi biasanya memakan waktu 1x24 jam.
        </p>
        <button
            class="inline-flex items-center gap-2 border border-outline-variant text-primary px-6 py-3 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all"
            @click="emit('upload-again')"
        >
            <IconRefresh :size="18" stroke="1.5" />
            Upload Ulang
        </button>
    </div>
</template>
