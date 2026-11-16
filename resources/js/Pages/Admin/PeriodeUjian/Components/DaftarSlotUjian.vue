<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    slots: { type: Array, default: () => [] },
    formatDate: { type: Function, required: true },
    tableKey: { type: String, default: 'admin-daftar-slot-ujian' },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function formatTimeRange(slotUjian) {
    const waktuMulai = slotUjian.waktu_mulai?.substring(0, 5) || '-';
    const waktuSelesai = slotUjian.waktu_selesai?.substring(0, 5) || '-';

    return `${waktuMulai} - ${waktuSelesai} WIB`;
}

const columns = [
    {
        accessorKey: 'tanggal',
        header: 'Tanggal',
        cell: info => props.formatDate(info.getValue()),
    },
    {
        accessorKey: 'waktu_mulai',
        header: 'Waktu',
        cell: info => formatTimeRange(info.row.original),
    },
    {
        accessorKey: 'kapasitas_peserta',
        header: 'Peserta',
        cell: info => `${info.row.original.pendaftaran_disetujui_count ?? 0}/${info.getValue() ?? 0}`,
    },
    {
        accessorKey: 'pendaftaran_ujian_count',
        header: 'Pendaftaran',
        cell: info => info.getValue() ?? 0,
    },
    {
        accessorKey: 'toleransi_keterlambatan_menit',
        header: 'Toleransi',
        cell: info => `${info.getValue() ?? 15} menit`,
    },
    {
        accessorKey: 'aktif',
        header: 'Status',
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'success' : 'neutral' },
            () => (info.getValue() ? 'Aktif' : 'Nonaktif'),
        ),
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => {
            const row = info.row.original;
            const deleteDisabled = row.sesi_ujian_count > 0 || row.pendaftaran_ujian_count > 0;

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Ubah sesi',
                        'aria-label': 'Ubah sesi',
                        onClick: () => emit('edit', row),
                    },
                    () => h(IconEdit, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        disabled: deleteDisabled,
                        title: deleteDisabled ? 'Tidak bisa dihapus - sudah memiliki peserta atau pendaftaran' : 'Hapus sesi',
                        'aria-label': deleteDisabled ? 'Tidak bisa dihapus - sudah memiliki peserta atau pendaftaran' : 'Hapus sesi',
                        onClick: () => emit('delete', row),
                    },
                    () => h(IconTrash, { size: 16 }),
                ),
            ]);
        },
    },
];
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="slots"
        :columns="columns"
        title="Daftar Sesi Ujian"
        searchable
        search-placeholder="Cari sesi ujian..."
    >
        <template #actions>
            <BaseTambahData @click="emit('create')" />
        </template>
    </AppDataTable>
</template>
