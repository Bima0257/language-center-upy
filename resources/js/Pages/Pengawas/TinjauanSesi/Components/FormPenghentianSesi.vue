<script setup>
const props = defineProps({
    form: { type: Object, required: true },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-error-red/30">
        <h2 class="text-title-lg font-semibold text-error-red mb-4">Hentikan Sesi</h2>
        <form @submit.prevent="emit('submit')" class="space-y-4">
            <BaseFormGrid>
                <div class="sm:col-span-2">
                    <label class="text-label-md font-medium text-text-heading block mb-2">Alasan</label>
                    <BaseTextarea v-model="props.form.alasan" rows="2" required
                                  placeholder="Alasan penghentian..." error />
                </div>
                <BaseButton type="submit" :disabled="props.form.processing" variant="danger" size="xl" class="w-full sm:col-span-2">
                    {{ props.form.processing ? 'Memproses...' : 'Hentikan Sesi' }}
                </BaseButton>
            </BaseFormGrid>
        </form>
    </div>
</template>
