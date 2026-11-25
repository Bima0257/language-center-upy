<script setup>
import { computed, h } from 'vue';
import { IconEye } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    title: { type: String, required: true },
    data: { type: Array, default: () => [] },
    columns: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, default: 'Cari...' },
    tableKey: { type: String, default: 'context-summary-table' },
});

const emit = defineEmits(['select']);

const tableColumns = computed(() => [
    ...props.columns,
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h(
            BaseButton,
            {
                variant: 'secondary',
                size: 'sm',
                class: 'p-2!',
                title: `Lihat ${props.title.toLowerCase()}`,
                'aria-label': `Lihat ${props.title.toLowerCase()}`,
                onClick: () => emit('select', info.row.original),
            },
            () => h(IconEye, { size: 18 }),
        ),
    },
]);
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="props.data"
        :columns="tableColumns"
        :title="props.title"
        :searchable="props.data.length > 0"
        :search-placeholder="props.searchPlaceholder"
    />
</template>
