<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';

defineProps({
    data: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <DataTable :data="data" :columns="columns" header-title="Daftar Bank Soal" searchable>
        <template #toolbar>
            <div class="flex items-center gap-2">
                <BaseTambahData @click="emit('create')" />
            </div>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center gap-2">
                <button
                    :disabled="row.bagian_ujian_count > 0"
                    @click="emit('edit', row)"
                    class="p-2 text-text-muted hover:text-secondary transition-colors disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:text-text-muted"
                    :title="row.bagian_ujian_count > 0 ? 'Tidak bisa diubah karena sudah digunakan di ujian' : 'Ubah'"
                >
                    <IconEdit :size="18" />
                </button>
                <button
                    @click="emit('delete', row)"
                    :disabled="row.soal_count > 0 || row.bagian_ujian_count > 0"
                    class="p-2 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted"
                    :title="row.soal_count > 0 || row.bagian_ujian_count > 0 ? 'Tidak bisa dihapus karena masih memiliki soal atau digunakan di ujian' : 'Hapus'"
                >
                    <IconTrash :size="18" />
                </button>
            </div>
        </template>
    </DataTable>
</template>
