<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    interpretasiSkor: { type: Array, default: () => [] },
    jenisTes: { type: Array, default: () => [] },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function examTypeName(id) {
    return props.jenisTes.find((type) => type.id === id)?.nama || '-';
}

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
    { key: 'skor_minimum', label: 'Rentang Skor', sortable: true, searchable: true, className: 'font-medium text-text-heading', render: scoreRange, slot: 'score-range' },
    { key: 'tingkat_cefr', label: 'CEFR', sortable: true },
    { key: 'label_tingkat', label: 'Label', sortable: true, searchable: true, render: labelSearchValue, slot: 'label' },
    { key: 'lulus', label: 'Kelulusan', sortable: true, searchable: true, render: statusLabel, slot: 'pass-status' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable
        :data="props.interpretasiSkor"
        :columns="columns"
        header-title="Daftar Interpretasi Skor"
        class="lg:col-span-2"
        searchable
    >
        <template #toolbar>
            <BaseTambahData @click="emit('create')" />
        </template>

        <template #score-range="{ row }">
            <p class="font-medium text-text-heading">{{ scoreRange(null, row) }}</p>
            <p class="text-text-muted text-label-md">{{ examTypeName(row.jenis_tes_id) }}</p>
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
                @click="emit('edit', row)"
            >
                <IconEdit :size="16" />
            </BaseButton>
            <BaseButton
                variant="danger"
                icon-only
                title="Hapus"
                aria-label="Hapus"
                @click="emit('delete', row)"
            >
                <IconTrash :size="16" />
            </BaseButton>
        </template>
    </DataTable>
</template>
