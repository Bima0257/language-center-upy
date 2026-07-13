<script setup>
defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <h3 class="text-title-lg font-semibold text-primary mb-2">{{ section.title }}</h3>
        <p class="text-text-muted text-body-md">Speaking — {{ section.total_questions }} task</p>
        <p class="text-text-muted text-label-md mt-2">Fitur speaking tersedia di fase berikutnya.</p>
    </div>
</template>
