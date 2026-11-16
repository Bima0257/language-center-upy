<script setup>
defineProps({
    partUjian: { type: Object, required: true },
});
</script>

<template>
    <BaseCard>
        <h3 class="text-title-lg font-semibold text-text-heading mb-2">{{ partUjian.judul }}</h3>
        <p class="text-text-muted text-body-md">Speaking — {{ partUjian.jumlah_soal }} task</p>
        <p class="text-text-muted text-label-md mt-2">Fitur speaking tersedia di fase berikutnya.</p>
    </BaseCard>
</template>
