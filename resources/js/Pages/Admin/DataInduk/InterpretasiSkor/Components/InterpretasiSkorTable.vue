<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    interpretasiSkor: { type: Array, default: () => [] },
    tableKey: { type: String, default: 'interpretasi-skor-table' },
    locked: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function scoreRange(row) {
    return `${row.skor_minimum} - ${row.skor_maksimum}`;
}

function statusLabel(value) {
    return value ? 'LULUS' : 'TIDAK LULUS';
}

const columns = [
    {
        accessorKey: 'skor_minimum',
        header: 'Rentang Skor',
        meta: { cellClass: 'font-medium text-text-heading' },
        cell: info => h('p', { class: 'font-medium text-text-heading' }, scoreRange(info.row.original)),
    },
    { accessorKey: 'tingkat_cefr', header: 'CEFR' },
    {
        accessorKey: 'label_tingkat',
        header: 'Label',
        cell: info => h('p', { class: 'text-body-md text-text-body' }, info.getValue()),
    },
    {
        accessorKey: 'lulus',
        header: 'Kelulusan',
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'success' : 'danger' },
            () => statusLabel(info.getValue()),
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
                    disabled: props.locked,
                    title: props.locked ? 'Tidak bisa diubah karena program tes dipakai ujian aktif' : 'Ubah',
                    'aria-label': props.locked ? 'Tidak bisa diubah karena program tes dipakai ujian aktif' : 'Ubah',
                    onClick: () => emit('edit', info.row.original),
                },
                () => h(IconEdit, { size: 16 }),
            ),
            h(
                BaseButton,
                {
                    variant: 'danger',
                    iconOnly: true,
                    disabled: props.locked,
                    title: props.locked ? 'Tidak bisa dihapus karena program tes dipakai ujian aktif' : 'Hapus',
                    'aria-label': props.locked ? 'Tidak bisa dihapus karena program tes dipakai ujian aktif' : 'Hapus',
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
        :data="props.interpretasiSkor"
        :columns="columns"
        title="Daftar Interpretasi Skor"
        class="lg:col-span-2"
        searchable
    >
        <template #actions>
            <BaseTambahData :disabled="props.locked" @click="emit('create')" />
        </template>
    </AppDataTable>
</template>
