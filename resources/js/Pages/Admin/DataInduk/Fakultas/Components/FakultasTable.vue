<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    fakultas: { type: Array, default: () => [] },
});

const emit = defineEmits(['create', 'edit', 'delete']);

const columns = [
    { key: 'nama', label: 'Nama', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'kode', label: 'Kode', sortable: true },
    { key: 'program_studi_count', label: 'Prodi', sortable: true, render: (value) => value ?? 0 },
    { key: 'aktif', label: 'Status', sortable: true, badge: true, render: (value) => value ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
    <DataTable :data="props.fakultas" :columns="columns" header-title="Daftar Fakultas" searchable>
        <template #toolbar>
            <BaseTambahData @click="emit('create')" />
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
