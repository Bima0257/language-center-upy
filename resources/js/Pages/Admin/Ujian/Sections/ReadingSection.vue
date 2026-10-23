<script setup>
defineProps({
    bagianUjian: { type: Object, required: true },
});
</script>

<template>
    <BaseCard>
        <h3 class="text-title-lg font-semibold text-text-heading mb-2">{{ bagianUjian.judul }}</h3>
        <p class="text-text-muted text-body-md">Reading — {{ bagianUjian.jumlah_soal }} soal</p>
    </BaseCard>
</template>
