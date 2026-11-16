<script setup>
import { h } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    fakultas: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
    tableKey: { type: String, default: 'program-studi-fakultas' },
});

const columns = [
    {
        accessorKey: 'nama',
        header: 'Fakultas',
        meta: { cellClass: 'font-medium text-text-heading' },
        cell: info => `${info.row.original.kode} - ${info.getValue()}`,
    },
    {
        accessorKey: 'program_studi_count',
        header: 'Jumlah Prodi',
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
                title: 'Lihat program studi',
                'aria-label': 'Lihat program studi',
            },
            () => h(IconEye, { size: 18 }),
        ),
    },
];
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="fakultas"
        :columns="columns"
        title="Daftar Fakultas"
        searchable
    />
</template>
