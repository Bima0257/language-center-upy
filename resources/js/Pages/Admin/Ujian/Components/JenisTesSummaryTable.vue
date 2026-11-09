<script setup>
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconEye } from '@tabler/icons-vue'

defineProps({
    ringkasanJenisTes: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
})

const columns = [
    { key: 'kode', label: 'Kode', sortable: true },
    { key: 'nama', label: 'Jenis Tes', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'jumlahUjian', label: 'Jumlah Ujian', sortable: true, render: value => value ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
]
</script>

<template>
    <DataTable
        :data="ringkasanJenisTes"
        :columns="columns"
        header-title="Daftar Jenis Tes"
        searchable
    >
        <template #actions="{ row }">
            <BaseButton
                :href="detailUrl(row.id)"
                variant="secondary"
                icon-only
                title="Detail"
                aria-label="Detail"
            >
                <IconEye :size="16" />
            </BaseButton>
        </template>
    </DataTable>
</template>
