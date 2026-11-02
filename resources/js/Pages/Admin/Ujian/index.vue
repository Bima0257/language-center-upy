<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import TambahUjianModal from './components/TambahUjianModal.vue'
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue'
import { useConfirm } from '@/Composables/useConfirm'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    ujian: { type: Array, default: () => [] },
    jenisTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
})

const confirm = useConfirm()
const showUjianModal = ref(false)
const editingUjian = ref(null)
const tambahForm = useForm({
    jenis_tes_id: '',
    bank_soal_id: '',
    judul: '',
    deskripsi: '',
    mode: 'tryout',
    durasi_menit: 160,
})
const editForm = useForm({
    jenis_tes_id: '',
    judul: '',
    deskripsi: '',
    mode: 'tryout',
    durasi_menit: 160,
    aktif: true,
})

const isCreating = computed(() => editingUjian.value === null)
const modalForm = computed(() => isCreating.value ? tambahForm : editForm)

const bankSoalTersaring = computed(() => {
    if (!tambahForm.jenis_tes_id) return []

    return props.bankSoal.filter((bank) =>
        String(bank.jenis_tes_id) === String(tambahForm.jenis_tes_id) && bank.aktif,
    )
})

watch(() => tambahForm.jenis_tes_id, () => {
    tambahForm.bank_soal_id = ''
})

function openTambahModal() {
    editingUjian.value = null
    tambahForm.reset()
    tambahForm.clearErrors()
    showUjianModal.value = true
}

function openEditModal(ujian) {
    editingUjian.value = ujian
    editForm.reset()
    editForm.clearErrors()
    editForm.jenis_tes_id = ujian.jenis_tes_id || ''
    editForm.judul = ujian.judul || ''
    editForm.deskripsi = ujian.deskripsi || ''
    editForm.mode = ujian.mode || 'tryout'
    editForm.durasi_menit = ujian.durasi_menit || 1
    editForm.aktif = !!ujian.aktif
    showUjianModal.value = true
}

function closeUjianModal() {
    showUjianModal.value = false
    editingUjian.value = null
    tambahForm.reset()
    tambahForm.clearErrors()
    editForm.reset()
    editForm.clearErrors()
}

function submitTambahUjian() {
    tambahForm.post(route('admin.ujian.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeUjianModal()
        },
    })
}

function submitEditUjian() {
    if (!editingUjian.value) return

    editForm.put(route('admin.ujian.perbarui', editingUjian.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeUjianModal()
        },
    })
}

async function destroy(ujian) {
    if (ujian.periode_ujian_exists) return
    if (!await confirm.confirm(`Hapus ujian "${ujian.judul}"? Tindakan ini tidak bisa dibatalkan.`)) return
    router.delete(route('admin.ujian.hapus', ujian.id), { preserveScroll: true })
}

const columns = [
    { key: 'judul', label: 'Judul Ujian', sortable: true, className: 'font-medium text-text-heading' },
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
                <BaseTambahData @click="openTambahModal" />
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="p-2 text-text-muted transition-colors hover:text-secondary"
                        title="Ubah"
                        @click="openEditModal(row)"
                    >
                        <IconEdit :size="18" />
                    </button>
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

        <TambahUjianModal
            :show="showUjianModal"
            :creating="isCreating"
            :form="modalForm"
            :jenis-tes="jenisTes"
            :bank-soal-tersaring="bankSoalTersaring"
            @close="closeUjianModal"
            @submit="isCreating ? submitTambahUjian() : submitEditUjian()"
        />
    </TataLetakDasbor>
</template>
