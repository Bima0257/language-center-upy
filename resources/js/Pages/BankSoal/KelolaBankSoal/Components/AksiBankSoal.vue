<script setup>
const emit = defineEmits(['back', 'create']);
</script>

<template>
    <div class="flex items-center justify-between gap-4 mb-6">
        <BaseBackButton @click="emit('back')" />
        <BaseTambahData @click="emit('create')" />
    </div>
</template>
