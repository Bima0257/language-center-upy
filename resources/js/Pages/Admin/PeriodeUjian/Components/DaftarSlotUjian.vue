<script setup>
import { IconPlus, IconTrash, IconEdit, IconClock } from '@tabler/icons-vue';

const props = defineProps({
    slotGroups: { type: Array, default: () => [] },
    formatDate: { type: Function, required: true },
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <BaseCard>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-title-lg font-semibold text-primary">Sesi Ujian per Hari</h2>
            <BaseButton size="sm" @click="emit('create')">
                <IconPlus :size="16" /> Tambah Sesi
            </BaseButton>
        </div>

        <p v-if="props.slotGroups.length === 0" class="text-text-muted text-body-md text-center py-10">
            Belum ada sesi. Tambahkan sesi per hari dalam periode ini.
        </p>

        <div v-else class="space-y-5">
            <div v-for="group in props.slotGroups" :key="group.tanggal" class="border border-outline-variant/50 rounded-2xl overflow-hidden">
                <div class="px-4 py-3 bg-surface-container-low flex items-center gap-2">
                    <IconClock :size="16" class="text-secondary" />
                    <p class="text-label-md font-semibold text-primary">{{ props.formatDate(group.tanggal) }}</p>
                </div>
                <div class="divide-y divide-outline-variant/30">
                    <div v-for="slotUjian in group.items" :key="slotUjian.id"
                         class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-surface-container-low/50 transition-colors">
                        <div>
                            <p class="text-body-md font-semibold text-primary">
                                {{ slotUjian.waktu_mulai?.substring(0, 5) }} – {{ slotUjian.waktu_selesai?.substring(0, 5) }} WIB
                            </p>
                            <p class="text-label-md text-text-muted">
                                {{ slotUjian.sesi_ujian_count || 0 }}/{{ slotUjian.kapasitas_peserta }} peserta —
                                toleransi {{ slotUjian.toleransi_keterlambatan_menit ?? 15 }} menit —
                                <span :class="slotUjian.aktif ? 'text-success' : 'text-error-red'">
                                    {{ slotUjian.aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button @click="emit('edit', slotUjian)" class="p-2 text-text-muted hover:text-secondary transition-colors">
                                <IconEdit :size="18" />
                            </button>
                            <button @click="emit('delete', slotUjian)"
                                    :disabled="slotUjian.sesi_ujian_count > 0"
                                    :title="slotUjian.sesi_ujian_count > 0 ? 'Tidak bisa dihapus — sudah diikuti peserta' : 'Hapus sesi'"
                                    class="p-2 transition-colors"
                                    :class="slotUjian.sesi_ujian_count > 0
                                        ? 'text-outline-variant cursor-not-allowed'
                                        : 'text-text-muted hover:text-error-red'">
                                <IconTrash :size="18" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BaseCard>
</template>
