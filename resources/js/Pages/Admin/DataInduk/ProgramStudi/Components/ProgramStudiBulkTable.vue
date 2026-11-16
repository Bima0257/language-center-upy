<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    programStudi: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 10 },
    tableKey: { type: String, default: 'admin-program-studi-bulk' },
});

const emit = defineEmits(['create', 'edit', 'delete']);

const columns = [
    { accessorKey: 'nama', header: 'Nama', meta: { cellClass: 'font-medium text-text-heading' } },
    { accessorKey: 'kode', header: 'Kode' },
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
            const index = props.programStudi.indexOf(info.row.original);

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Ubah',
                        'aria-label': 'Ubah',
                        onClick: () => emit('edit', index),
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
                        onClick: () => emit('delete', index),
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
        :data="props.programStudi"
        :columns="columns"
        title="Daftar Program Studi Baru"
        searchable
        :show-page-size="false"
    >
        <template #actions>
            <span class="text-label-md text-text-muted">{{ props.programStudi.length }}/{{ props.maxRows }} data</span>
            <BaseTambahData
                :disabled="props.programStudi.length >= props.maxRows"
                @click="emit('create')"
            />
        </template>
    </AppDataTable>
</template>
