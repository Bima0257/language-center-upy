<script setup>
import { IconArrowLeft, IconPlus } from '@tabler/icons-vue';

const emit = defineEmits(['back', 'create']);
</script>

<template>
    <div class="flex items-center justify-between gap-4 mb-6">
        <button
            type="button"
            @click="emit('back')"
            class="inline-flex items-center gap-2 text-secondary text-label-md font-medium hover:underline"
        >
            <IconArrowLeft :size="16" /> Kembali
        </button>
        <BaseButton size="sm" @click="emit('create')">
            <IconPlus :size="16" /> Tambah Bank Soal
        </BaseButton>
    </div>
</template>
