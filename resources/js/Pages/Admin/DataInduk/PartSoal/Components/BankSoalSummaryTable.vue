<script setup>
import { h } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    ringkasanBankSoal: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
    tableKey: { type: String, default: 'part-soal-bank-soal-summary' },
});

const columns = [
    { accessorKey: 'nama', header: 'Bank Soal', meta: { cellClass: 'font-medium text-text-heading' } },
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
                title: 'Lihat part soal',
                'aria-label': 'Lihat part soal',
            },
            () => h(IconEye, { size: 18 }),
        ),
    },
];
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="ringkasanBankSoal"
        :columns="columns"
        title="Daftar Bank Soal"
        searchable
    />
</template>
