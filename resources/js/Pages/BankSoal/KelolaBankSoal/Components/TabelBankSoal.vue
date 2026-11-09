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
            <BaseButton
                variant="secondary"
                icon-only
                :disabled="row.bagian_ujian_count > 0"
                :title="row.bagian_ujian_count > 0 ? 'Tidak bisa diubah karena sudah digunakan di ujian' : 'Ubah'"
                :aria-label="row.bagian_ujian_count > 0 ? 'Tidak bisa diubah karena sudah digunakan di ujian' : 'Ubah'"
                @click="emit('edit', row)"
            >
                <IconEdit :size="16" />
            </BaseButton>
            <BaseButton
                variant="danger"
                icon-only
                :disabled="row.soal_count > 0 || row.bagian_ujian_count > 0"
                :title="row.soal_count > 0 || row.bagian_ujian_count > 0 ? 'Tidak bisa dihapus karena masih memiliki soal atau digunakan di ujian' : 'Hapus'"
                :aria-label="row.soal_count > 0 || row.bagian_ujian_count > 0 ? 'Tidak bisa dihapus karena masih memiliki soal atau digunakan di ujian' : 'Hapus'"
                @click="emit('delete', row)"
            >
                <IconTrash :size="16" />
            </BaseButton>
        </template>
    </DataTable>
</template>
