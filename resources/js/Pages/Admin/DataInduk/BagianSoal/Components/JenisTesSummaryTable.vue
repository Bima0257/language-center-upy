<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEye } from '@tabler/icons-vue';

defineProps({
    ringkasanJenisTes: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
});

const columns = [
    { key: 'nama', label: 'Jenis Tes', sortable: true, className: 'font-medium text-primary' },
    { key: 'jumlahBankSoal', label: 'Bank Soal', sortable: true, render: (val) => val ?? 0 },
    { key: 'jumlahBagian', label: 'Bagian', sortable: true, render: (val) => val ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable :data="ringkasanJenisTes" :columns="columns" header-title="Daftar Jenis Tes" searchable>
        <template #actions="{ row }">
            <BaseButton
                :href="detailUrl(row.id)"
                variant="secondary"
                size="sm"
                class="!p-2"
                title="Lihat bank soal"
                aria-label="Lihat bank soal"
            >
                <IconEye :size="18" />
            </BaseButton>
        </template>
    </DataTable>
</template>
