<script setup>
const props = defineProps({
    periodeUjian: { type: Object, required: true },
    formatDate: { type: Function, required: true },
});
</script>

<template>
    <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <div>
            <p class="text-text-body text-body-md">{{ props.periodeUjian.ujian?.judul }}</p>
            <p class="text-text-muted text-label-md">
                Periode: {{ props.formatDate(props.periodeUjian.tanggal_mulai) }} s/d {{ props.formatDate(props.periodeUjian.tanggal_selesai) }}
            </p>
        </div>
    </div>
</template>
