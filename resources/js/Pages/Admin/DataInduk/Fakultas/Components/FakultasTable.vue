<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    fakultas: { type: Array, default: () => [] },
    tableKey: { type: String, default: 'admin-fakultas' },
});

const emit = defineEmits(['create', 'edit', 'delete']);

const columns = [
    { accessorKey: 'nama', header: 'Nama', meta: { cellClass: 'font-medium text-text-heading' } },
    { accessorKey: 'kode', header: 'Kode' },
    { accessorKey: 'program_studi_count', header: 'Prodi', cell: info => info.getValue() ?? 0 },
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
        cell: info => h('div', { class: 'flex items-center justify-center gap-1' }, [
            h(
                BaseButton,
                {
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Ubah',
                    'aria-label': 'Ubah',
                    onClick: () => emit('edit', info.row.original),
                },
                () => h(IconEdit, { size: 16 }),
            ),
            h(
                BaseButton,
                {
                    variant: 'danger',
                    iconOnly: true,
                    title: 'Hapus',
                    'aria-label': 'Hapus',
                    onClick: () => emit('delete', info.row.original),
                },
                () => h(IconTrash, { size: 16 }),
            ),
        ]),
    },
];
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="props.fakultas"
        :columns="columns"
        title="Daftar Fakultas"
        searchable
    >
        <template #actions>
            <BaseTambahData @click="emit('create')" />
        </template>
    </AppDataTable>
</template>
