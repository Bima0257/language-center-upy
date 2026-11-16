<script setup>
import { h } from 'vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import { formatProgramTes } from '@/constants/programTes';

const props = defineProps({
    interpretasiSkor: { type: Array, default: () => [] },
    programTes: { type: Array, default: () => [] },
    tableKey: { type: String, default: 'interpretasi-skor-table' },
    readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function examTypeName(id) {
    return formatProgramTes(props.programTes.find((type) => type.id === id));
}

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
        cell: info => h('div', [
            h('p', { class: 'font-medium text-text-heading' }, scoreRange(info.row.original)),
            h('p', { class: 'text-text-muted text-label-md' }, examTypeName(info.row.original.program_tes_id)),
        ]),
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
        cell: info => (props.readOnly
            ? '-'
            : h('div', { class: 'flex items-center justify-center gap-1' }, [
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
            ])),
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
        <template v-if="!props.readOnly" #actions>
            <BaseTambahData @click="emit('create')" />
        </template>
    </AppDataTable>
</template>
