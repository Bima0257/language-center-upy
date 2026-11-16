<script setup>
defineProps({
    partUjian: { type: Object, required: true },
});
</script>

<template>
    <BaseCard>
        <h3 class="text-title-lg font-semibold text-text-heading mb-2">{{ partUjian.judul }}</h3>
        <p class="text-text-muted text-body-md">Listening — {{ partUjian.jumlah_soal }} soal</p>
    </BaseCard>
</template>
