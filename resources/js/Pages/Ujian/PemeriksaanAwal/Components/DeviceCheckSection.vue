<script setup>
import { IconCheck, IconDeviceDesktop, IconX } from '@tabler/icons-vue';

defineProps({
    deviceType: { type: String, default: null },
    deviceOk: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
        <div class="flex items-center gap-3">
            <IconDeviceDesktop class="text-text-muted" :size="22" stroke="1.5" />
            <div>
                <p class="text-label-md font-medium text-primary">Perangkat</p>
                <p class="text-text-muted text-xs">
                    <template v-if="deviceType === 'desktop'">Laptop / Komputer — Terdeteksi</template>
                    <template v-else-if="deviceType === 'mobile'">HP — Tidak diperbolehkan</template>
                    <template v-else-if="deviceType === 'tablet'">Tablet — Tidak diperbolehkan</template>
                    <template v-else>Memeriksa...</template>
                </p>
            </div>
        </div>
        <IconCheck v-if="deviceOk" class="text-success" :size="22" />
        <IconX v-else-if="deviceType" class="text-error-red" :size="22" />
    </div>
    <p v-if="deviceType && !deviceOk" class="text-error-red text-xs -mt-2 px-4">
        Ujian hanya dapat diakses dari laptop atau komputer.
    </p>
</template>
