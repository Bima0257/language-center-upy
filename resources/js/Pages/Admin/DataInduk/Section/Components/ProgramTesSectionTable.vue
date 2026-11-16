<script setup>
import { h } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import { formatProgramTes } from '@/constants/programTes';

const props = defineProps({
    programTes: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
    tableKey: { type: String, default: 'section-program-tes' },
});

const columns = [
    {
        id: 'program_tes',
        header: 'Program Tes',
        meta: { cellClass: 'font-medium text-text-heading' },
        accessorFn: row => formatProgramTes(row),
    },
    {
        accessorKey: 'section_count',
        header: 'Jumlah Section',
        cell: info => info.getValue() ?? 0,
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h(
            BaseButton,
            {
                href: props.detailUrl(info.row.original.id),
                variant: 'secondary',
                size: 'sm',
                class: '!p-2',
                title: 'Lihat section',
                'aria-label': 'Lihat section',
            },
            () => h(IconEye, { size: 18 }),
        ),
    },
];
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="programTes"
        :columns="columns"
        title="Daftar Program Tes"
        searchable
    />
</template>
