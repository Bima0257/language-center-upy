<script setup>
import { h } from 'vue';
import { IconEdit } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    aturanPenilaian: { type: Array, default: () => [] },
    ubahUrl: { type: Function, required: true },
    locked: { type: Boolean, default: false },
});

const columns = [
    {
        accessorKey: 'nama_section',
        header: 'Section',
        meta: { cellClass: 'font-medium text-text-heading' },
    },
    { accessorKey: 'kode_section', header: 'Kode' },
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
                href: props.locked ? undefined : props.ubahUrl(info.row.original),
                variant: 'secondary',
                size: 'sm',
                class: 'p-2!',
                disabled: props.locked,
                title: props.locked ? 'Tidak bisa diubah karena program tes dipakai ujian aktif' : 'Ubah aturan penilaian',
                'aria-label': props.locked ? 'Tidak bisa diubah karena program tes dipakai ujian aktif' : 'Ubah aturan penilaian',
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
