<script setup>
import { IconAlertTriangle, IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    message: { type: String, default: '' },
    strike: { type: Number, default: 0 },
});

defineEmits(['close', 'back']);
</script>

<template>
    <BaseModal :show="show" max-width="md" :closeable="false">
        <div class="p-8">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-warning-container rounded-full flex items-center justify-center shrink-0">
                    <IconAlertTriangle class="text-warning" :size="24" />
                </div>
                <div class="flex-1">
                    <h3 id="violation-modal-title" class="text-title-lg font-semibold text-text-heading mb-1">Peringatan Ke-{{ strike }}</h3>
                    <p class="text-text-body text-body-md">{{ message || 'Aktivitas mencurigakan terdeteksi.' }}</p>
                    <p class="text-error-red text-label-md mt-2 font-medium">3 pelanggaran = sesi dihentikan.</p>
                    <button
                        type="button"
                        @click="$emit('back')"
                        class="mt-4 px-5 py-2.5 rounded-full text-label-md font-semibold bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary transition-all shadow-md active:scale-95 duration-150"
                    >
                        Kembali ke Ujian
                    </button>
                </div>
                <button type="button" aria-label="Tutup peringatan" @click="$emit('close')" class="text-text-muted hover:text-text-heading transition-colors shrink-0">
                    <IconX :size="20" />
                </button>
            </div>
        </div>
    </BaseModal>
</template>
