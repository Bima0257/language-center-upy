<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    programStudi: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 10 },
});

const emit = defineEmits(['create', 'edit', 'delete']);

const columns = [
    { key: '_index', label: 'No.', render: (_value, row) => props.programStudi.indexOf(row) + 1 },
    { key: 'nama', label: 'Nama', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'kode', label: 'Kode', sortable: true },
    { key: 'aktif', label: 'Status', sortable: true, badge: true, render: (value) => value ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function rowIndex(program) {
    return props.programStudi.indexOf(program);
}
</script>

<template>
    <DataTable
        :data="props.programStudi"
        :columns="columns"
        header-title="Daftar Program Studi Baru"
        :show-page-size="false"
        searchable
    >
        <template #toolbar>
            <span class="text-label-md text-text-muted">{{ props.programStudi.length }}/{{ props.maxRows }} data</span>
            <BaseTambahData
                :disabled="props.programStudi.length >= props.maxRows"
                @click="emit('create')"
            />
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
