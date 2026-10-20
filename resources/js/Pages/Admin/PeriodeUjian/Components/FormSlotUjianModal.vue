<script setup>
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    periodeUjian: { type: Object, required: true },
    slotDateMin: { type: String, default: '' },
    slotDateMax: { type: String, default: '' },
    formatDate: { type: Function, required: true },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="props.show" @close="emit('close')" max-width="2xl">
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-headline-md font-bold text-primary">{{ props.creating ? 'Tambah Sesi' : 'Ubah Sesi' }}</h2>
                <button @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                    <IconX :size="20" />
                </button>
            </div>
            <form @submit.prevent="emit('submit')" class="space-y-5">
                <div>
                    <label class="text-label-md font-medium text-primary block mb-2">Tanggal Sesi</label>
                    <input type="date" v-model="props.form.tanggal" required
                           :min="props.slotDateMin" :max="props.slotDateMax"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.tanggal" class="text-error-red text-xs mt-1">{{ props.form.errors.tanggal }}</p>
                    <p class="text-label-md text-text-muted mt-1">
                        Hanya tanggal dalam periode {{ props.formatDate(props.periodeUjian.tanggal_mulai) }} s/d {{ props.formatDate(props.periodeUjian.tanggal_selesai) }} yang bisa dipilih.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Jam Mulai</label>
                        <input type="time" v-model="props.form.waktu_mulai" required
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.waktu_mulai" class="text-error-red text-xs mt-1">{{ props.form.errors.waktu_mulai }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Jam Selesai</label>
                        <input type="time" v-model="props.form.waktu_selesai" required
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.waktu_selesai" class="text-error-red text-xs mt-1">{{ props.form.errors.waktu_selesai }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Toleransi (menit)</label>
                        <input type="number" v-model="props.form.toleransi_keterlambatan_menit" min="0"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Kuota Peserta</label>
                        <input type="number" v-model="props.form.kapasitas_peserta" min="1"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                </div>
                <label v-if="!props.creating" class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="props.form.aktif" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Aktif</span>
                </label>
                <div class="flex gap-4 pt-2">
                    <BaseButton type="submit" :disabled="props.form.processing" size="xl" class="flex-1">
                        {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Tambah Sesi' : 'Simpan Perubahan') }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                        Batal
                    </BaseButton>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
