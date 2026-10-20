<script setup>
import { IconCamera, IconCheck } from '@tabler/icons-vue';

defineProps({
    cameraOk: { type: Boolean, default: false },
    cameraChecking: { type: Boolean, default: false },
    cameraError: { type: String, default: '' },
    photoPreview: { type: String, default: null },
});

const emit = defineEmits(['check']);
</script>

<template>
    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
        <div class="flex items-center gap-3">
            <IconCamera class="text-text-muted" :size="22" stroke="1.5" />
            <div>
                <p class="text-label-md font-medium text-primary">Kamera</p>
                <p class="text-text-muted text-xs">
                    <template v-if="cameraOk && !photoPreview">Terdeteksi — Siap ambil foto</template>
                    <template v-else-if="cameraOk && photoPreview">Foto berhasil diambil</template>
                    <template v-else>Belum dicek</template>
                </p>
            </div>
        </div>
        <BaseButton v-if="!cameraOk" size="sm" :disabled="cameraChecking" @click="emit('check')">
            {{ cameraChecking ? 'Mengecek...' : 'Cek Kamera' }}
        </BaseButton>
        <IconCheck v-else class="text-success" :size="22" />
    </div>
    <p v-if="!cameraOk && !cameraChecking" class="text-text-muted text-xs -mt-2 px-4">
        Klik "Cek Kamera" lalu izinkan akses kamera pada browser Anda.
    </p>
    <p v-if="cameraError" class="text-error-red text-xs -mt-2 px-4">
        {{ cameraError }}
    </p>
</template>
