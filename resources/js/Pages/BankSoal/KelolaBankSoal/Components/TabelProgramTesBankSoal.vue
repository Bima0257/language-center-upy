<script setup>
import { computed, h } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    tableKey: { type: String, default: 'admin-program-tes-bank-soal' },
});

const tableColumns = computed(() => [
    ...props.columns,
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => {
            const row = info.row.original;

            if (!row.id) return h('span', { class: 'text-text-muted' }, '-');

            return h(
                BaseButton,
                {
                    href: route('pustaka-soal.bank-soal.program-tes', row.id),
                    variant: 'secondary',
                    size: 'sm',
                    class: '!p-2',
                    title: 'Lihat detail program tes',
                    'aria-label': 'Lihat detail program tes',
                },
                () => h(IconEye, { size: 18 }),
            );
        },
    },
]);
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="data"
        :columns="tableColumns"
        title="Daftar Program Tes"
        searchable
    />
</template>
