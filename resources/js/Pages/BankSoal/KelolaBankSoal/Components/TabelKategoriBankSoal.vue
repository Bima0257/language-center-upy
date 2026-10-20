<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEye } from '@tabler/icons-vue';

defineProps({
    data: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
});

</script>

<template>
    <DataTable :data="data" :columns="columns" header-title="Daftar Kategori Bank Soal" searchable>
        <template #actions="{ row }">
            <BaseButton
                v-if="row.id"
                :href="route('pustaka-soal.bank-soal.kategori', row.id)"
                variant="secondary"
                size="sm"
                class="!p-2"
                title="Lihat detail kategori"
                aria-label="Lihat detail kategori"
            >
                <IconEye :size="18" />
            </BaseButton>
            <span v-else class="text-text-muted">-</span>
        </template>
    </DataTable>
</template>
