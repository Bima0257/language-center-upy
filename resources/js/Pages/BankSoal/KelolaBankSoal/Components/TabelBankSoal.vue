<script setup>
import { computed, h } from 'vue';
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    tableKey: { type: String, default: 'admin-bank-soal' },
});

const emit = defineEmits(['create', 'detail', 'edit', 'delete']);

const tableColumns = computed(() => [
    ...props.columns,
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => {
            const row = info.row.original;
            const editDisabled = row.part_ujian_count > 0;
            const deleteDisabled = row.soal_count > 0 || row.part_ujian_count > 0;

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Lihat detail bank soal',
                        'aria-label': 'Lihat detail bank soal',
                        onClick: () => emit('detail', row),
                    },
                    () => h(IconEye, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        disabled: editDisabled,
                        title: editDisabled ? 'Tidak bisa diubah karena sudah digunakan di ujian' : 'Ubah',
                        'aria-label': editDisabled ? 'Tidak bisa diubah karena sudah digunakan di ujian' : 'Ubah',
                        onClick: () => emit('edit', row),
                    },
                    () => h(IconEdit, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        disabled: deleteDisabled,
                        title: deleteDisabled ? 'Tidak bisa dihapus karena masih memiliki soal atau digunakan di ujian' : 'Hapus',
                        'aria-label': deleteDisabled ? 'Tidak bisa dihapus karena masih memiliki soal atau digunakan di ujian' : 'Hapus',
                        onClick: () => emit('delete', row),
                    },
                    () => h(IconTrash, { size: 16 }),
                ),
            ]);
        },
    },
]);
</script>

<template>
    <AppDataTable
        :table-key="props.tableKey"
        :data="data"
        :columns="tableColumns"
        title="Daftar Bank Soal"
        searchable
    >
        <template #actions>
            <BaseTambahData @click="emit('create')" />
        </template>
    </AppDataTable>
</template>
