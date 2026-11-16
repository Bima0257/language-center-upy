<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import BaseBadge from '@/Components/Base/BaseBadge.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import BaseTambahData from '@/Components/Base/BaseTambahData.vue'
import AppDataTable from '@/Components/Ui/AppDataTable.vue'
import TambahUjianModal from './Components/TambahUjianModal.vue'
import ProgramTesSummaryTable from './Components/ProgramTesSummaryTable.vue'
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue'
import { useConfirm } from '@/Composables/useConfirm'
import { computed, h, ref, watch } from 'vue'

const props = defineProps({
    ujian: { type: Array, default: () => [] },
    programTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
    ringkasanProgramTes: { type: Array, default: () => [] },
    programTesTerpilih: { type: Object, default: null },
    programTesTerpilihId: { type: [String, null], default: null },
})

const confirm = useConfirm()
const programTesFilter = computed(() => props.programTesTerpilihId || '')
const showUjianModal = ref(false)
const editingUjian = ref(null)
const tambahForm = useForm({
    program_tes_id: '',
    bank_soal_id: '',
    judul: '',
    deskripsi: '',
    mode: 'tryout',
    durasi_menit: 160,
})
const editForm = useForm({
    program_tes_id: '',
    judul: '',
    deskripsi: '',
    mode: 'tryout',
    durasi_menit: 160,
    aktif: true,
})

const isCreating = computed(() => editingUjian.value === null)
const modalForm = computed(() => isCreating.value ? tambahForm : editForm)

const bankSoalTersaring = computed(() => {
    if (!tambahForm.program_tes_id) return []

    return props.bankSoal.filter((bank) =>
        String(bank.program_tes_id) === String(tambahForm.program_tes_id) && bank.aktif,
    )
})

watch(() => tambahForm.program_tes_id, () => {
    tambahForm.bank_soal_id = ''
})

function openTambahModal() {
    editingUjian.value = null
    tambahForm.reset()
    tambahForm.clearErrors()
    tambahForm.program_tes_id = programTesFilter.value
    showUjianModal.value = true
}

function openEditModal(ujian) {
    editingUjian.value = ujian
    editForm.reset()
    editForm.clearErrors()
    editForm.program_tes_id = ujian.program_tes_id || ''
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

function programTesUrl(programTesId) {
    return `${route('admin.ujian.daftar')}?program_tes_id=${encodeURIComponent(programTesId)}`
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
    { accessorKey: 'judul', header: 'Judul Ujian', meta: { cellClass: 'font-medium text-text-heading' } },
    {
        accessorKey: 'mode',
        header: 'Mode',
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() === 'tryout' ? 'pastel-blue' : 'pastel' },
            () => (info.getValue() === 'tryout' ? 'Try Out' : 'Ujian Resmi'),
        ),
    },
    { accessorKey: 'durasi_menit', header: 'Durasi', cell: info => `${info.getValue()} menit` },
    { accessorKey: 'part_ujian_count', header: 'Part Ujian', cell: info => info.getValue() ?? 0 },
    {
        accessorKey: 'aktif',
        header: 'Status',
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'success' : 'neutral' },
            () => (info.getValue() ? 'Aktif' : 'Nonaktif'),
        ),
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => {
            const row = info.row.original

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Ubah',
                        'aria-label': 'Ubah',
                        onClick: () => openEditModal(row),
                    },
                    () => h(IconEdit, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        href: route('admin.ujian.detail', row.id),
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Detail',
                        'aria-label': 'Detail',
                    },
                    () => h(IconEye, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        disabled: row.periode_ujian_exists,
                        title: row.periode_ujian_exists ? 'Tidak bisa dihapus - sudah terjadwal' : 'Hapus ujian',
                        'aria-label': row.periode_ujian_exists ? 'Tidak bisa dihapus - sudah terjadwal' : 'Hapus ujian',
                        onClick: () => destroy(row),
                    },
                    () => h(IconTrash, { size: 16 }),
                ),
            ])
        },
    },
]
</script>

<template>
    <Head :title="programTesTerpilih ? `Manajemen Ujian - ${programTesTerpilih.nama}` : 'Manajemen Ujian'" />
    <TataLetakDasbor
        :title="programTesTerpilih ? `Manajemen Ujian - ${programTesTerpilih.nama}` : 'Manajemen Ujian'"
        :subtitle="programTesTerpilih ? 'Atur daftar ujian, part ujian, dan komposisi soal' : 'Pilih program tes untuk melihat daftar ujian'"
        :back-href="programTesFilter ? route('admin.ujian.daftar') : null"
    >
        <template v-if="!programTesFilter">
            <ProgramTesSummaryTable
                table-key="admin-ujian-program-tes"
                :ringkasan-program-tes="ringkasanProgramTes"
                :detail-url="programTesUrl"
            />
        </template>

        <template v-else>
            <AppDataTable
                table-key="admin-ujian"
                :data="ujian"
                :columns="columns"
                searchable
                title="Daftar Ujian"
            >
                <template #actions>
                    <BaseTambahData @click="openTambahModal" />
                </template>
            </AppDataTable>

            <TambahUjianModal
                :show="showUjianModal"
                :creating="isCreating"
                :form="modalForm"
                :program-tes="programTes"
                :bank-soal-tersaring="bankSoalTersaring"
                @close="closeUjianModal"
                @submit="isCreating ? submitTambahUjian() : submitEditUjian()"
            />
        </template>
    </TataLetakDasbor>
</template>
