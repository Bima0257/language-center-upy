<script setup>
import { h } from 'vue';
import { IconEdit } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

defineProps({
    aturanPenilaian: { type: Array, default: () => [] },
});

const emit = defineEmits(['edit']);

const columns = [
    {
        accessorKey: 'nama_section',
        header: 'Section',
        meta: { cellClass: 'font-medium text-text-heading' },
    },
    { accessorKey: 'kode_section', header: 'Kode' },
    {
        accessorKey: 'terkonfigurasi',
        header: 'Status',
        cell: info => (info.getValue() ? 'Terkonfigurasi' : 'Belum dikonfigurasi'),
    },
    {
        accessorKey: 'skor_mentah_maksimum',
        header: 'Raw Maks.',
        cell: info => info.getValue() ?? '-',
    },
    {
        accessorKey: 'skor_skala_minimum',
        header: 'Skala Min.',
        cell: info => info.getValue() ?? '-',
    },
    {
        accessorKey: 'skor_skala_maksimum',
        header: 'Skala Maks.',
        cell: info => info.getValue() ?? '-',
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h(
            BaseButton,
            {
                variant: 'secondary',
                size: 'sm',
                class: '!p-2',
                title: 'Ubah aturan penilaian',
                'aria-label': 'Ubah aturan penilaian',
                onClick: () => emit('edit', info.row.original),
            },
            () => h(IconEdit, { size: 18 }),
        ),
    },
];
</script>

<template>
    <AppDataTable
        table-key="admin-aturan-penilaian"
        :data="aturanPenilaian"
        :columns="columns"
        title="Konfigurasi Section"
        searchable
    />
</template>
