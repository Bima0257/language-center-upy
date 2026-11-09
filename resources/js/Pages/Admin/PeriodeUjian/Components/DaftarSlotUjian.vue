<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconTrash, IconEdit } from '@tabler/icons-vue';

const props = defineProps({
    slots: { type: Array, default: () => [] },
    formatDate: { type: Function, required: true },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function formatTimeRange(slotUjian) {
    const waktuMulai = slotUjian.waktu_mulai?.substring(0, 5) || '-';
    const waktuSelesai = slotUjian.waktu_selesai?.substring(0, 5) || '-';

    return `${waktuMulai} – ${waktuSelesai} WIB`;
}

const columns = [
    {
        key: 'tanggal',
        label: 'Tanggal',
        sortable: true,
        render: value => props.formatDate(value),
    },
    {
        key: 'waktu_mulai',
        label: 'Waktu',
        sortable: true,
        render: (_value, row) => formatTimeRange(row),
    },
    {
        key: 'kapasitas_peserta',
        label: 'Peserta',
        sortable: true,
        render: (_value, row) => `${row.pendaftaran_disetujui_count ?? 0}/${row.kapasitas_peserta ?? 0}`,
    },
    {
        key: 'pendaftaran_ujian_count',
        label: 'Pendaftaran',
        sortable: true,
        render: value => value ?? 0,
    },
    {
        key: 'toleransi_keterlambatan_menit',
        label: 'Toleransi',
        sortable: true,
        render: value => `${value ?? 15} menit`,
    },
    {
        key: 'aktif',
        label: 'Status',
        sortable: true,
        badge: true,
        render: value => value ? 'Aktif' : 'Nonaktif',
    },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable
        :data="slots"
        :columns="columns"
        header-title="Daftar Sesi Ujian"
        searchable
        search-placeholder="Cari sesi ujian..."
    >
        <template #toolbar>
            <BaseTambahData @click="emit('create')" />
        </template>

        <template #actions="{ row }">
            <BaseButton
                variant="secondary"
                icon-only
                title="Ubah sesi"
                aria-label="Ubah sesi"
                @click="emit('edit', row)"
            >
                <IconEdit :size="16" />
            </BaseButton>
            <BaseButton
                variant="danger"
                icon-only
                :disabled="row.sesi_ujian_count > 0 || row.pendaftaran_ujian_count > 0"
                :title="row.sesi_ujian_count > 0 || row.pendaftaran_ujian_count > 0 ? 'Tidak bisa dihapus — sudah memiliki peserta atau pendaftaran' : 'Hapus sesi'"
                :aria-label="row.sesi_ujian_count > 0 || row.pendaftaran_ujian_count > 0 ? 'Tidak bisa dihapus — sudah memiliki peserta atau pendaftaran' : 'Hapus sesi'"
                @click="emit('delete', row)"
            >
                <IconTrash :size="16" />
            </BaseButton>
        </template>
    </DataTable>
</template>
