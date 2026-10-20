<script setup>
defineProps({
    form: { type: Object, required: true },
    canSubmit: { type: Boolean, default: true },
    cancelHref: { type: String, required: true },
});
</script>

<template>
    <div class="flex gap-4">
        <BaseButton type="submit" :disabled="form.processing || !canSubmit" size="xl" class="flex-1">{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</BaseButton>
        <BaseButton :href="cancelHref" variant="secondary" size="lg">Batal</BaseButton>
    </div>
</template>
