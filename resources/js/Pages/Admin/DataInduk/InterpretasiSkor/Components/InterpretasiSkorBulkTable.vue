<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    interpretasiSkor: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 10 },
    tableKey: { type: String, default: 'interpretasi-skor-bulk' },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function scoreRange(row) {
    return `${row.skor_minimum} - ${row.skor_maksimum}`;
}

function statusLabel(value) {
    return value ? 'LULUS' : 'TIDAK LULUS';
}

function rowIndex(interpretasi) {
    return props.interpretasiSkor.indexOf(interpretasi);
}

const columns = [
    {
        accessorKey: 'skor_minimum',
        header: 'Rentang Skor',
        meta: { cellClass: 'font-medium text-text-heading' },
        cell: info => scoreRange(info.row.original),
    },
    { accessorKey: 'tingkat_cefr', header: 'CEFR' },
    {
        accessorKey: 'label_tingkat',
        header: 'Label',
        cell: info => h('div', [
            h('p', { class: 'text-body-md text-text-body' }, info.getValue()),
            info.row.original.deskripsi
                ? h('p', { class: 'text-text-muted text-label-md' }, info.row.original.deskripsi)
                : null,
        ]),
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
        cell: info => {
            const index = rowIndex(info.row.original);

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
        :data="props.interpretasiSkor"
        :columns="columns"
        title="Daftar Interpretasi Skor Baru"
        searchable
        :show-page-size="false"
    >
        <template #actions>
            <span class="text-label-md text-text-muted">{{ props.interpretasiSkor.length }}/{{ props.maxRows }} data</span>
            <BaseTambahData
                :disabled="props.interpretasiSkor.length >= props.maxRows"
                @click="emit('create')"
            />
        </template>
    </AppDataTable>
</template>
