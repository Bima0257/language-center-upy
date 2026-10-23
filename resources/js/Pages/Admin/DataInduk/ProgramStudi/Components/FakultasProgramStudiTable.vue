<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEye } from '@tabler/icons-vue';

defineProps({
    fakultas: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
});

const columns = [
    { key: 'nama', label: 'Fakultas', sortable: true, className: 'font-medium text-text-heading', render: (val, row) => `${row.kode} - ${val}` },
    { key: 'program_studi_count', label: 'Jumlah Prodi', sortable: true, render: (val) => val ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable :data="fakultas" :columns="columns" header-title="Daftar Fakultas" searchable>
        <template #actions="{ row }">
            <BaseButton
                :href="detailUrl(row.id)"
                variant="secondary"
                size="sm"
                class="!p-2"
                title="Lihat program studi"
                aria-label="Lihat program studi"
            >
                <IconEye :size="18" />
            </BaseButton>
        </template>
    </DataTable>
</template>
