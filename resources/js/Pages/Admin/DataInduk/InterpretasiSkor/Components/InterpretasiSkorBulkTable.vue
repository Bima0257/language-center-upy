<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    interpretasiSkor: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 10 },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function scoreRange(_value, row) {
    return `${row.skor_minimum} - ${row.skor_maksimum}`;
}

function labelSearchValue(value, row) {
    return [value, row.deskripsi].filter(Boolean).join(' ');
}

function statusLabel(value) {
    return value ? 'LULUS' : 'TIDAK LULUS';
}

const columns = [
    { key: '_index', label: 'No.', render: (_value, row) => props.interpretasiSkor.indexOf(row) + 1 },
    { key: 'skor_minimum', label: 'Rentang Skor', sortable: true, render: scoreRange, className: 'font-medium text-text-heading' },
    { key: 'tingkat_cefr', label: 'CEFR', sortable: true },
    { key: 'label_tingkat', label: 'Label', sortable: true, searchable: true, render: labelSearchValue, slot: 'label' },
    { key: 'lulus', label: 'Kelulusan', sortable: true, searchable: true, render: statusLabel, slot: 'pass-status' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function rowIndex(interpretasi) {
    return props.interpretasiSkor.indexOf(interpretasi);
}
</script>

<template>
    <DataTable
        :data="props.interpretasiSkor"
        :columns="columns"
        header-title="Daftar Interpretasi Skor Baru"
        :show-page-size="false"
        searchable
    >
        <template #toolbar>
            <span class="text-label-md text-text-muted">{{ props.interpretasiSkor.length }}/{{ props.maxRows }} data</span>
            <BaseTambahData
                :disabled="props.interpretasiSkor.length >= props.maxRows"
                @click="emit('create')"
            />
        </template>

        <template #label="{ row }">
            <p class="text-body-md text-text-body">{{ row.label_tingkat }}</p>
            <p v-if="row.deskripsi" class="text-text-muted text-label-md">{{ row.deskripsi }}</p>
        </template>

        <template #pass-status="{ row }">
            <BaseBadge :variant="row.lulus ? 'success' : 'danger'">
                {{ statusLabel(row.lulus) }}
            </BaseBadge>
        </template>

        <template #actions="{ row }">
            <BaseButton
                variant="secondary"
                icon-only
                title="Ubah"
                aria-label="Ubah"
                @click="emit('edit', rowIndex(row))"
            >
                <IconEdit :size="16" />
            </BaseButton>
            <BaseButton
                variant="danger"
                icon-only
                title="Hapus"
                aria-label="Hapus"
                @click="emit('delete', rowIndex(row))"
            >
                <IconTrash :size="16" />
            </BaseButton>
        </template>
    </DataTable>
</template>
