<script setup>
const props = defineProps({
    sesiUjian: { type: Object, required: true },
});
</script>

<template>
    <BaseCard>
        <h2 class="text-title-lg font-semibold text-text-heading mb-4">Skor</h2>
        <div class="grid grid-cols-4 gap-4 text-center">
            <div class="bg-surface-container-low rounded-2xl p-4">
                <p class="text-text-muted text-label-md">Membaca</p>
                <p class="text-headline-md font-bold text-text-heading">{{ props.sesiUjian.skor_membaca ?? '-' }}</p>
            </div>
            <div class="bg-surface-container-low rounded-2xl p-4">
                <p class="text-text-muted text-label-md">Mendengar</p>
                <p class="text-headline-md font-bold text-text-heading">{{ props.sesiUjian.skor_menyimak ?? '-' }}</p>
            </div>
            <div class="bg-surface-container-low rounded-2xl p-4">
                <p class="text-text-muted text-label-md">Berbicara</p>
                <p class="text-headline-md font-bold text-text-heading">{{ props.sesiUjian.skor_berbicara ?? '-' }}</p>
            </div>
            <div class="bg-surface-container-low rounded-2xl p-4">
                <p class="text-text-muted text-label-md">Menulis</p>
                <p class="text-headline-md font-bold text-text-heading">{{ props.sesiUjian.skor_menulis ?? '-' }}</p>
            </div>
        </div>
        <div class="mt-4 text-center">
            <p class="text-text-muted text-label-md">Total</p>
            <p class="text-headline-lg font-bold text-text-heading">{{ props.sesiUjian.skor_total }}/120</p>
        </div>
    </BaseCard>
</template>
