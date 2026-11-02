<script setup>
defineProps({
    processing: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    showCancel: { type: Boolean, default: true },
    cancelHref: { type: String, default: null },
    saveType: { type: String, default: 'submit' },
    form: { type: String, default: null },
});

const emit = defineEmits(['cancel', 'save']);
</script>

<template>
    <div class="base-form-actions flex flex-wrap justify-end gap-2 border-t border-outline-variant/30 pt-4">
        <BaseButton
            v-if="showCancel"
            type="button"
            variant="secondary"
            size="sm"
            :href="cancelHref"
            @click="emit('cancel', $event)"
        >
            Batal
        </BaseButton>
        <BaseButton
            :type="saveType"
            :form="form"
            size="sm"
            :disabled="processing || disabled"
            @click="emit('save', $event)"
        >
            {{ processing ? 'Menyimpan...' : 'Simpan' }}
        </BaseButton>
    </div>
</template>
