<script setup>
import { h } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import { formatProgramTes } from '@/constants/programTes';

const props = defineProps({
    ringkasanProgramTes: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
    tableKey: { type: String, default: 'part-soal-program-tes-summary' },
});

const columns = [
    {
        id: 'program_tes',
        header: 'Program Tes',
        meta: { cellClass: 'font-medium text-text-heading' },
        accessorFn: row => formatProgramTes(row),
    },
    { accessorKey: 'jumlahBankSoal', header: 'Bank Soal', cell: info => info.getValue() ?? 0 },
    { accessorKey: 'jumlahPart', header: 'Part', cell: info => info.getValue() ?? 0 },
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
                class: 'p-2!',
                title: 'Lihat bank soal',
                'aria-label': 'Lihat bank soal',
            },
            () => h(IconEye, { size: 18 }),
        ),
    },
];
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="ringkasanProgramTes"
        :columns="columns"
        title="Daftar Program Tes"
        searchable
    />
</template>
