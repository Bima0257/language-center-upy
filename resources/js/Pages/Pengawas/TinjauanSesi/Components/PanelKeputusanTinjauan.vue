<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';

const props = defineProps({
    sesiUjian: { type: Object, required: true },
    form: { type: Object, required: true },
});

const emit = defineEmits(['submit']);

const reviewOptions = [
    { id: 'sah', name: '✅ Sah' },
    { id: 'ujian_ulang', name: '🔄 Ujian Ulang' },
    { id: 'dibatalkan', name: '❌ Dibatalkan' },
];

const reviewStatusColors = {
    sah: 'bg-success-container text-on-success-container',
    ujian_ulang: 'bg-warning-container text-on-warning-container',
    dibatalkan: 'bg-error-container text-on-error-container',
};

function formatDateTime(value) {
    return value ? new Date(value).toLocaleString('id-ID') : '';
}
</script>

<template>
    <BaseCard class="h-fit sticky top-24">
        <h2 class="text-title-lg font-semibold text-primary mb-4">Keputusan Tinjauan</h2>
        <form @submit.prevent="emit('submit')" class="space-y-4">
            <div>
                <DropDown
                    v-model="props.form.status_tinjauan"
                    :options="reviewOptions"
                    label="Status"
                    placeholder="Pilih keputusan"
                    option-label="name"
                    option-value="id"
                />
            </div>
            <div>
                <label class="text-label-md font-medium text-primary block mb-2">Catatan</label>
                <BaseTextarea v-model="props.form.catatan_tinjauan" rows="5"
                              placeholder="Alasan dan detail keputusan..." />
            </div>
            <BaseButton type="submit" :disabled="props.form.processing" size="xl" class="w-full">
                {{ props.form.processing ? 'Menyimpan...' : 'Simpan Keputusan' }}
            </BaseButton>
        </form>

        <div v-if="props.sesiUjian.status_tinjauan" class="mt-6 pt-6 border-t border-outline-variant/30">
            <h3 class="text-label-md font-medium text-primary mb-2">Hasil Tinjauan</h3>
            <span class="inline-block px-3 py-1 rounded-full text-label-md font-medium"
                  :class="reviewStatusColors[props.sesiUjian.status_tinjauan] || 'bg-neutral-container text-on-neutral-container'">
                {{ props.sesiUjian.status_tinjauan }}
            </span>
            <p v-if="props.sesiUjian.catatan_tinjauan" class="text-text-body text-text-body text-body-md mt-2">{{ props.sesiUjian.catatan_tinjauan }}</p>
            <p v-if="props.sesiUjian.peninjau_id" class="text-text-muted text-label-md mt-2">
                Oleh: {{ props.sesiUjian.peninjau?.name }} — {{ formatDateTime(props.sesiUjian.ditinjau_pada) }}
            </p>
        </div>
    </BaseCard>
</template>
