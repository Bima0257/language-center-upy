<script setup>
import { IconAlertTriangle } from '@tabler/icons-vue';

defineProps({
    violations: { type: Array, default: () => [] },
});

const violationTypeLabel = (type) => {
    const labels = {
        tab_switch: 'Pindah Tab',
        fullscreen_exit: 'Keluar Fullscreen',
        copy_paste: 'Copy-Paste',
        right_click: 'Klik Kanan',
        devtools: 'Buka DevTools',
        multiple_login: 'Login Ganda',
        heartbeat_lost: 'Koneksi Hilang',
        window_blur: 'Pindah Aplikasi',
        print_attempt: 'Mencetak Halaman',
    };
    return labels[type] || type.replace(/_/g, ' ');
};
</script>

<template>
    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <h3 class="text-title-lg font-semibold text-primary mb-4">Log Pelanggaran</h3>
        <div v-if="violations.length === 0" class="text-text-muted text-body-md">Tidak ada pelanggaran.</div>
        <div v-else class="space-y-3">
            <div v-for="v in violations" :key="v.id"
                 class="flex items-start gap-3 p-3 bg-surface-container-low rounded-2xl">
                <IconAlertTriangle class="text-amber-500 shrink-0 mt-0.5" :size="18" stroke="1.5" />
                <div>
                    <p class="text-label-md font-medium text-primary">{{ violationTypeLabel(v.type) }}</p>
                    <p class="text-label-md text-text-muted">{{ v.created_at }}</p>
                </div>
                <span class="ml-auto text-label-md font-medium"
                      :class="v.strike_count >= 3 ? 'text-error-red' : 'text-amber-500'">
                    Pelanggaran #{{ v.strike_count }}
                </span>
            </div>
        </div>
    </div>
</template>
