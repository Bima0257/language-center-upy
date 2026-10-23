<script setup>
defineProps({
    keterampilanName: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-6">
        <h2 class="text-headline-md font-bold text-text-heading">Ubah Soal</h2>
        <p class="text-text-muted text-text-body text-body-md mt-1">Pilihan Ganda — {{ keterampilanName || 'Unknown' }}</p>
    </div>
</template>
