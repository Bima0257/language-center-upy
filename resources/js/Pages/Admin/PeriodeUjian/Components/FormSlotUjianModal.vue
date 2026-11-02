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
    <BaseModal
        :show="props.show"
        @close="emit('close')"
        max-width="2xl"
        aria-labelledby="slot-ujian-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="slot-ujian-modal-title" class="text-headline-md font-bold text-text-heading">{{ props.creating ? 'Tambah Sesi' : 'Ubah Sesi' }}</h2>
                         <button type="button" aria-label="Tutup sesi ujian" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <form id="form-slot-ujian" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <div>
                    <label class="text-label-md font-medium text-text-heading block mb-2">Tanggal Sesi</label>
                    <input type="date" v-model="props.form.tanggal" required
                           :min="props.slotDateMin" :max="props.slotDateMax"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.tanggal" class="text-error-red text-xs mt-1">{{ props.form.errors.tanggal }}</p>
                    <p class="text-label-md text-text-muted mt-1">
                        Hanya tanggal dalam periode {{ props.formatDate(props.periodeUjian.tanggal_mulai) }} s/d {{ props.formatDate(props.periodeUjian.tanggal_selesai) }} yang bisa dipilih.
                    </p>
                </div>
                <BaseFormGrid>
                    <div>
                    <label class="text-label-md font-medium text-text-heading block mb-2">Jam Mulai</label>
                        <input type="time" v-model="props.form.waktu_mulai" required
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.waktu_mulai" class="text-error-red text-xs mt-1">{{ props.form.errors.waktu_mulai }}</p>
                    </div>
                    <div>
                    <label class="text-label-md font-medium text-text-heading block mb-2">Jam Selesai</label>
                        <input type="time" v-model="props.form.waktu_selesai" required
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.waktu_selesai" class="text-error-red text-xs mt-1">{{ props.form.errors.waktu_selesai }}</p>
                    </div>
                </BaseFormGrid>
                <BaseFormGrid>
                    <div>
                    <label class="text-label-md font-medium text-text-heading block mb-2">Toleransi (menit)</label>
                        <input type="number" v-model="props.form.toleransi_keterlambatan_menit" min="0"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                    <div>
                    <label class="text-label-md font-medium text-text-heading block mb-2">Kuota Peserta</label>
                        <input type="number" v-model="props.form.kapasitas_peserta" min="1"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                </BaseFormGrid>
                <BaseToggle v-if="!props.creating" v-model="props.form.aktif" label="Sesi ujian aktif" />
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-slot-ujian"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
