<script setup>
import { Link } from '@inertiajs/vue3';
import { IconArrowRight, IconCalendarEvent, IconClock, IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    periodeUjian: { type: Object, required: true },
    formatDate: { type: Function, required: true },
});

const emit = defineEmits(['delete']);
</script>

<template>
    <div class="flex items-center justify-between hover:border-secondary/50 transition-colors bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30">
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <IconCalendarEvent class="text-secondary shrink-0" :size="18" />
                <h3 class="text-title-lg font-semibold text-text-heading truncate">{{ props.periodeUjian.judul }}</h3>
            </div>
            <p class="text-text-body text-label-md">
                {{ props.periodeUjian.ujian?.judul }} — {{ props.formatDate(props.periodeUjian.tanggal_mulai) }} s/d {{ props.formatDate(props.periodeUjian.tanggal_selesai) }}
            </p>
            <p class="text-text-muted text-xs mt-1">
                <span class="inline-flex items-center gap-1"><IconClock :size="14" /> {{ props.periodeUjian.slot_ujian_count || 0 }} sesi</span>
                —
                <span :class="props.periodeUjian.aktif ? 'text-success' : 'text-error-red'">
                    {{ props.periodeUjian.aktif ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <div class="flex items-center gap-1 shrink-0">
            <Link :href="route('admin.periode-ujian.detail', props.periodeUjian.id)"
                  class="flex items-center gap-1 px-3 py-2 rounded-full text-label-md font-medium text-secondary hover:bg-surface-container-low transition-colors">
                Kelola Sesi <IconArrowRight :size="16" />
            </Link>
            <Link :href="route('admin.periode-ujian.ubah', props.periodeUjian.id)"
                  class="p-2 text-text-muted hover:text-secondary transition-colors">
                <IconEdit :size="20" />
            </Link>
            <button @click="emit('delete', props.periodeUjian)"
                    :disabled="props.periodeUjian.slot_ujian_count > 0"
                    :title="props.periodeUjian.slot_ujian_count > 0 ? 'Tidak bisa dihapus — sudah punya sesi' : 'Hapus periode'"
                    class="p-2 transition-colors"
                    :class="props.periodeUjian.slot_ujian_count > 0
                        ? 'text-outline-variant cursor-not-allowed'
                        : 'text-text-muted hover:text-error-red'">
                <IconTrash :size="20" />
            </button>
        </div>
    </div>
</template>
