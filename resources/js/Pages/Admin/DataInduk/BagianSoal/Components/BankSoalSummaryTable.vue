<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconArrowLeft, IconEye } from '@tabler/icons-vue';

defineProps({
    ringkasanBankSoal: { type: Array, default: () => [] },
    detailUrl: { type: Function, required: true },
    backUrl: { type: String, required: true },
});

const columns = [
    { key: 'nama', label: 'Bank Soal', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'jumlahBagian', label: 'Bagian', sortable: true, render: (val) => val ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable :data="ringkasanBankSoal" :columns="columns" header-title="Daftar Bank Soal" searchable>
        <template #toolbar>
            <BaseButton variant="secondary" size="sm" :href="backUrl">
                <IconArrowLeft :size="16" /> Kembali
            </BaseButton>
        </template>

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
