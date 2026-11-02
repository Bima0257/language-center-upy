<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEye } from '@tabler/icons-vue';

defineProps({
    ringkasanBankSoal: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
});

const columns = [
    { key: 'nama', label: 'Bank Soal', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'jumlahBagian', label: 'Bagian', sortable: true, render: (val) => val ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable :data="ringkasanBankSoal" :columns="columns" header-title="Daftar Bank Soal" searchable>
        <template #actions="{ row }">
            <BaseButton
                :href="detailUrl(row.id)"
                variant="secondary"
                size="sm"
                class="!p-2"
                title="Lihat bagian soal"
                aria-label="Lihat bagian soal"
            >
                <IconEye :size="18" />
            </BaseButton>
        </template>
    </DataTable>
</template>
