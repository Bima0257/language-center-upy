<script setup>
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEdit, IconTrash } from '@tabler/icons-vue';

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
                :disabled="row.bank_soal_count > 0 || row.ujian_count > 0 || row.aturan_penilaian_count > 0 || row.interpretasi_skor_count > 0"
                :title="(row.bank_soal_count > 0 || row.ujian_count > 0 || row.aturan_penilaian_count > 0 || row.interpretasi_skor_count > 0) ? 'Tidak bisa dihapus karena masih digunakan konfigurasi ujian' : 'Hapus'"
                :aria-label="(row.bank_soal_count > 0 || row.ujian_count > 0 || row.aturan_penilaian_count > 0 || row.interpretasi_skor_count > 0) ? 'Tidak bisa dihapus karena masih digunakan konfigurasi ujian' : 'Hapus'"
                @click="emit('delete', row)"
            >
                <IconTrash :size="16" />
            </BaseButton>
        </template>
    </DataTable>
</template>
