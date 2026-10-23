<script setup>
import { Link } from '@inertiajs/vue3';
import { IconAlertTriangle } from '@tabler/icons-vue';

const props = defineProps({
    sesiDitandai: { type: Array, default: () => [] },
});
</script>

<template>
    <div>
        <h2 class="text-title-lg font-semibold text-text-heading mb-4 flex items-center gap-2">
            <IconAlertTriangle class="text-warning" :size="22" />
            Sesi Perlu Tinjauan ({{ props.sesiDitandai.length }})
        </h2>
        <div v-if="props.sesiDitandai.length === 0" class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <p class="text-text-muted text-body-md">Semua sesi dalam keadaan baik.</p>
        </div>
        <div v-else class="space-y-3">
            <div v-for="sesiUjian in props.sesiDitandai" :key="sesiUjian.id"
                 class="bg-surface-white rounded-2xl p-6 shadow-soft border border-error-red/30 hover:border-error-red transition-colors">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold text-text-heading">{{ sesiUjian.pengguna?.name }}</p>
                        <p class="text-text-muted text-label-md">{{ sesiUjian.pengguna?.nim || 'NIM: -' }}</p>
                        <p class="text-text-muted text-label-md">{{ sesiUjian.alasan_penandaan }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-label-md font-medium" :class="sesiUjian.jumlah_pelanggaran >= 3 ? 'text-error-red' : 'text-warning'">
                            {{ sesiUjian.jumlah_pelanggaran }} pelanggaran
                        </p>
                        <Link :href="route('pengawas.sesi.detail', sesiUjian.id)"
                              class="text-secondary text-label-md font-medium hover:underline">
                            Tinjau →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
