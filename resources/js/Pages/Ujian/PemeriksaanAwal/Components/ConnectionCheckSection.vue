<script setup>
import { IconCheck, IconWifi, IconX } from '@tabler/icons-vue';

defineProps({
    connectionOk: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
        <div class="flex items-center gap-3">
            <IconWifi class="text-text-muted" :size="22" stroke="1.5" />
            <div>
                <p class="text-label-md font-medium text-primary">Koneksi Internet</p>
                <p class="text-text-muted text-xs">{{ connectionOk ? 'Stabil' : 'Bermasalah' }}</p>
            </div>
        </div>
        <IconCheck v-if="connectionOk" class="text-success" :size="22" />
        <IconX v-else class="text-error-red" :size="22" />
    </div>
</template>
