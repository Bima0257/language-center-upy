<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { IconPlus, IconEye, IconTrash } from '@tabler/icons-vue'
import { useConfirm } from '@/Composables/useConfirm'

defineProps({
    ujian: { type: Array, default: () => [] },
})

const confirm = useConfirm()

async function destroy(ujian) {
    if (ujian.periode_ujian_exists) return
    if (!await confirm.confirm(`Hapus ujian "${ujian.judul}"? Tindakan ini tidak bisa dibatalkan.`)) return
    router.delete(route('admin.ujian.hapus', ujian.id), { preserveScroll: true })
}

const columns = [
    { key: 'judul', label: 'Judul Ujian', sortable: true, className: 'font-medium text-primary' },
    { key: 'mode', label: 'Mode', sortable: true, badge: true,
      render: (val) => val === 'tryout' ? 'Try Out' : 'Ujian Resmi' },
    { key: 'durasi_menit', label: 'Durasi',
      render: (val) => `${val} menit` },
    { key: 'bagian_ujian_count', label: 'Bagian Ujian',
      render: (val) => val ?? 0 },
    { key: 'aktif', label: 'Status', sortable: true, badge: true,
      render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
]
</script>

<template>
    <Head title="Manajemen Ujian" />
    <TataLetakDasbor title="Manajemen Ujian" subtitle="Atur daftar ujian, bagianUjian, dan komposisi soal">
        <DataTable
            :data="ujian"
            :columns="columns"
            searchable
            header-title="Daftar Ujian"
        >
            <template #toolbar>
                <BaseButton size="sm" :href="route('admin.ujian.tambah')">
                    <IconPlus :size="16" /> Buat Ujian Baru
                </BaseButton>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.ujian.detail', row.id)"
                          class="p-2 text-text-muted hover:text-secondary transition-colors" title="Detail">
                        <IconEye :size="18" />
                    </Link>
                    <button
                        @click="destroy(row)"
                        :disabled="row.periode_ujian_exists"
                        :title="row.periode_ujian_exists ? 'Tidak bisa dihapus — sudah terjadwal' : 'Hapus ujian'"
                        class="p-2 transition-colors"
                        :class="row.periode_ujian_exists
                            ? 'text-outline-variant cursor-not-allowed'
                            : 'text-text-muted hover:text-error-red'"
                    >
                        <IconTrash :size="18" />
                    </button>
                </div>
            </template>
        </DataTable>
    </TataLetakDasbor>
</template>
