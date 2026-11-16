<script setup>
import { computed } from 'vue';

const props = defineProps({
    sesiUjian: { type: Object, required: true },
});

const skorSection = computed(() => {
    const hasil = props.sesiUjian.hasil_penilaian || [];

    if (hasil.length > 0) {
        return hasil.map((item) => ({
            label: item.section?.nama || 'Section',
            value: item.skor_skala ?? '-',
        }));
    }

    return [
        { label: 'Membaca', value: props.sesiUjian.skor_membaca ?? '-' },
        { label: 'Mendengar', value: props.sesiUjian.skor_menyimak ?? '-' },
        { label: 'Berbicara', value: props.sesiUjian.skor_berbicara ?? '-' },
        { label: 'Menulis', value: props.sesiUjian.skor_menulis ?? '-' },
    ];
});
</script>

<template>
    <BaseCard>
        <h2 class="text-title-lg font-semibold text-text-heading mb-4">Skor</h2>
        <div class="grid grid-cols-2 gap-4 text-center md:grid-cols-4">
            <div v-for="skor in skorSection" :key="skor.label" class="bg-surface-container-low rounded-2xl p-4">
                <p class="text-text-muted text-label-md">{{ skor.label }}</p>
                <p class="text-headline-md font-bold text-text-heading">{{ skor.value }}</p>
            </div>
        </div>
        <div class="mt-4 text-center">
            <p class="text-text-muted text-label-md">Total</p>
            <p class="text-headline-lg font-bold text-text-heading">
                {{ props.sesiUjian.skor_total ?? '-' }}/{{ props.sesiUjian.skor_total_maksimum ?? '-' }}
            </p>
            <p v-if="props.sesiUjian.tingkat_cefr" class="text-label-md text-text-muted">
                {{ props.sesiUjian.tingkat_cefr }} — {{ props.sesiUjian.label_tingkat }}
                ({{ props.sesiUjian.lulus ? 'Lulus' : 'Tidak Lulus' }})
            </p>
        </div>
    </BaseCard>
</template>
