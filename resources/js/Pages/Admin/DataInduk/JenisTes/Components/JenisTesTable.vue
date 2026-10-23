<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconPlus, IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    jenisTes: { type: Array, default: () => [] },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

const columns = [
    { key: 'kode', label: 'Kode', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'nama', label: 'Nama', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'keterampilan_count', label: 'Keterampilan', render: (val) => val ?? 0 },
    { key: 'batas_pelanggaran', label: 'Maks. Strikes', render: (val) => val ?? 3 },
    { key: 'deskripsi', label: 'Deskripsi', render: (val) => { const text = stripHtml(val); return text ? (text.length > 60 ? text.substring(0, 60) + '…' : text) : '-'; } },
    { key: 'aktif', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];
</script>

<template>
        <DataTable :data="props.jenisTes" :columns="columns" header-title="Daftar Jenis Tes" searchable>
        <template #toolbar>
            <BaseButton size="sm" @click="emit('create')">
                <IconPlus :size="16" /> Tambah Jenis Tes
            </BaseButton>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center gap-2">
                <button @click="emit('edit', row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah"><IconEdit :size="18" /></button>
                <button @click="emit('delete', row)" :disabled="row.bank_soal_count > 0 || row.ujian_count > 0 || row.aturan_penilaian_count > 0 || row.interpretasi_skor_count > 0"
                        class="p-2 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted"
                        :title="(row.bank_soal_count > 0 || row.ujian_count > 0 || row.aturan_penilaian_count > 0 || row.interpretasi_skor_count > 0) ? 'Tidak bisa dihapus karena masih digunakan konfigurasi ujian' : 'Hapus'">
                    <IconTrash :size="18" />
                </button>
            </div>
        </template>
    </DataTable>
</template>
