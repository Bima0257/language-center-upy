<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEye } from '@tabler/icons-vue';

defineProps({
    jenisTes: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
});

const columns = [
    { key: 'nama', label: 'Jenis Tes', sortable: true, className: 'font-medium text-text-heading', render: (val, row) => `${row.kode} - ${val}` },
    { key: 'keterampilan_count', label: 'Jumlah Keterampilan', sortable: true, render: (val) => val ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable :data="jenisTes" :columns="columns" header-title="Daftar Jenis Tes" searchable>
        <template #actions="{ row }">
            <BaseButton
                :href="detailUrl(row.id)"
                variant="secondary"
                size="sm"
                class="!p-2"
                title="Lihat keterampilan"
                aria-label="Lihat keterampilan"
            >
                <IconEye :size="18" />
            </BaseButton>
        </template>
    </DataTable>
</template>
