<script setup>
import { Head } from "@inertiajs/vue3";
import ExamTakePreview from "@/Components/Ujian/ExamTakePreview/ExamTakePreview.vue";

defineProps({
    sesiUjian: { type: Object, required: true },
    overview: { type: Object, default: () => ({}) },
    blocks: { type: Array, default: () => [] },
    backUrl: { type: String, default: null },
    manageUrl: { type: String, default: null },
    initialSoalId: { type: String, default: null },
});
</script>

<template>
    <Head title="Pratinjau Ujian" />
    <ExamTakePreview
        :sesi-ujian="sesiUjian"
        :overview="overview"
        :blocks="blocks"
        :back-url="backUrl"
        :manage-url="manageUrl"
        :initial-soal-id="initialSoalId"
    />
</template>
