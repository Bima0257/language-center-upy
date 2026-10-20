<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconArrowLeft, IconEdit, IconPlus, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    backUrl: { type: String, required: true },
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <DataTable :data="data" :columns="columns" header-title="Daftar Bank Soal" searchable>
        <template #toolbar>
            <div class="flex items-center gap-2">
                <BaseButton variant="secondary" size="sm" :href="props.backUrl">
                    <IconArrowLeft :size="16" /> Kembali
                </BaseButton>
                <BaseButton size="sm" @click="emit('create')">
                    <IconPlus :size="16" /> Tambah Bank Soal
                </BaseButton>
            </div>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center gap-2">
                <button @click="emit('edit', row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah">
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
