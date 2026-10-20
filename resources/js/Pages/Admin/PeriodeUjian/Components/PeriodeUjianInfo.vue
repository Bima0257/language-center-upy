<script setup>
import { Link } from '@inertiajs/vue3';
import { IconArrowLeft } from '@tabler/icons-vue';

const props = defineProps({
    periodeUjian: { type: Object, required: true },
    formatDate: { type: Function, required: true },
});
</script>

<template>
    <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-body text-body-md">{{ props.periodeUjian.ujian?.judul }}</p>
                <p class="text-text-muted text-label-md">
                    Periode: {{ props.formatDate(props.periodeUjian.tanggal_mulai) }} s/d {{ props.formatDate(props.periodeUjian.tanggal_selesai) }}
                </p>
            </div>
            <Link :href="route('admin.periode-ujian.daftar')"
                  class="flex items-center gap-2 text-secondary text-label-md font-medium hover:underline">
                <IconArrowLeft :size="16" /> Penjadwalan
            </Link>
        </div>
    </div>
</template>
