<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import { formatProgramTes } from '@/constants/programTes';

const props = defineProps({
    programTes: { type: Array, default: () => [] },
    tableKey: { type: String, default: 'admin-program-tes' },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

function canDelete(row) {
    return !(
        row.bank_soal_count > 0
        || row.ujian_count > 0
        || row.aturan_penilaian_count > 0
        || row.interpretasi_skor_count > 0
        || row.skema_penilaian_count > 0
    );
}

const columns = [
    {
        id: 'program_tes',
        header: 'Program Tes',
        meta: { cellClass: 'font-medium text-text-heading' },
        accessorFn: row => formatProgramTes(row),
    },
    { accessorKey: 'section_count', header: 'Section', cell: info => info.getValue() ?? 0 },
    { accessorKey: 'batas_pelanggaran', header: 'Maks. Strikes', cell: info => info.getValue() ?? 3 },
    {
        accessorKey: 'deskripsi',
        header: 'Deskripsi',
        cell: info => {
            const text = stripHtml(info.getValue());

            return text ? (text.length > 60 ? `${text.substring(0, 60)}...` : text) : '-';
        },
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
            const deletable = canDelete(row);

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Ubah',
                        'aria-label': 'Ubah',
                        onClick: () => emit('edit', row),
                    },
                    () => h(IconEdit, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        disabled: !deletable,
                        title: deletable ? 'Hapus' : 'Tidak bisa dihapus karena masih digunakan konfigurasi ujian',
                        'aria-label': deletable ? 'Hapus' : 'Tidak bisa dihapus karena masih digunakan konfigurasi ujian',
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
        :data="props.programTes"
        :columns="columns"
        title="Daftar Program Tes"
        searchable
    >
        <template #actions>
            <BaseTambahData @click="emit('create')" />
        </template>
    </AppDataTable>
</template>
