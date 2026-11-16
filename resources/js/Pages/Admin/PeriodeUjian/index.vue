<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import BaseBadge from '@/Components/Base/BaseBadge.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import BaseTambahData from '@/Components/Base/BaseTambahData.vue'
import ContextSummaryTable from '@/Components/Ujian/ContextSummaryTable.vue'
import AppDataTable from '@/Components/Ui/AppDataTable.vue'
import ProgramTesSummaryTable from '@/Pages/Admin/PeriodeUjian/Components/ProgramTesSummaryTable.vue'
import FormPeriodeUjianModal from '@/Pages/Admin/PeriodeUjian/Components/FormPeriodeUjianModal.vue'
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue'
import { computed, h, ref } from 'vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

const props = defineProps({
    periodeUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    ringkasanProgramTes: { type: Array, default: () => [] },
    ringkasanUjian: { type: Array, default: () => [] },
    programTesTerpilih: { type: Object, default: null },
    programTesTerpilihId: { type: [String, null], default: null },
    ujianTerpilih: { type: Object, default: null },
    ujianTerpilihId: { type: [String, null], default: null },
})

const programTesFilter = computed(() => props.programTesTerpilihId || '')
const ujianFilter = computed(() => props.ujianTerpilihId || '')
const showPeriodModal = ref(false)
const creatingPeriod = ref(false)
const editingPeriod = ref(null)

const createForm = useForm({
    judul: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
})

const editForm = useForm({
    judul: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    aktif: true,
})

const modalForm = computed(() => creatingPeriod.value ? createForm : editForm)

const pageTitle = computed(() => {
    if (props.ujianTerpilih) return `Periode Ujian - ${props.ujianTerpilih.judul}`
    if (props.programTesTerpilih) return `Daftar Ujian - ${props.programTesTerpilih.nama}`

    return 'Penjadwalan Ujian'
})

const pageSubtitle = computed(() => {
    if (props.ujianTerpilih) return 'Kelola periode dan slot jadwal ujian'
    if (props.programTesTerpilih) return 'Pilih ujian untuk melihat dan mengelola periode'

    return 'Pilih program tes untuk melihat daftar ujian'
})

const backHref = computed(() => {
    if (ujianFilter.value) return programTesUrl(programTesFilter.value)
    if (programTesFilter.value) return route('admin.periode-ujian.daftar')

    return null
})

const tableFilters = [
    {
        key: 'aktif',
        label: 'Status',
        options: [
            { value: '1', label: 'Aktif' },
            { value: '0', label: 'Nonaktif' },
        ],
    },
]

const tableFixedFilters = computed(() => {
    const filters = {}

    if (programTesFilter.value) filters.program_tes_id = programTesFilter.value
    if (ujianFilter.value) filters.ujian_id = ujianFilter.value

    return filters
})

const ujianColumns = [
    { accessorKey: 'judul', header: 'Ujian', meta: { cellClass: 'font-medium text-text-heading' } },
    {
        accessorKey: 'mode',
        header: 'Mode',
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() === 'tryout' ? 'pastel-blue' : 'pastel' },
            () => (info.getValue() === 'tryout' ? 'Try Out' : 'Ujian Resmi'),
        ),
    },
    { accessorKey: 'jumlahPeriode', header: 'Periode', cell: info => info.getValue() ?? 0 },
]

const periodeColumns = [
    {
        accessorKey: 'judul',
        header: 'Periode',
        cell: info => h('p', { class: 'font-semibold text-text-heading' }, info.getValue()),
    },
    {
        accessorKey: 'tanggal_mulai',
        header: 'Rentang Tanggal',
        cell: info => `${formatDate(info.row.original.tanggal_mulai)} s/d ${formatDate(info.row.original.tanggal_selesai)}`,
    },
    {
        accessorKey: 'slot_ujian_count',
        header: 'Sesi',
        cell: info => `${info.getValue() ?? 0} sesi`,
    },
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
            const deleteDisabled = row.slot_ujian_count > 0

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        href: route('admin.periode-ujian.detail', row.id),
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Kelola sesi',
                        'aria-label': 'Kelola sesi',
                    },
                    () => h(IconEye, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Ubah periode',
                        'aria-label': 'Ubah periode',
                        onClick: () => openEditPeriod(row),
                    },
                    () => h(IconEdit, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        disabled: deleteDisabled,
                        title: deleteDisabled ? 'Tidak bisa dihapus - sudah punya sesi' : 'Hapus periode',
                        'aria-label': deleteDisabled ? 'Tidak bisa dihapus - sudah punya sesi' : 'Hapus periode',
                        onClick: () => destroy(row),
                    },
                    () => h(IconTrash, { size: 16 }),
                ),
            ])
        },
    },
]

function programTesUrl(programTesId) {
    return `${route('admin.periode-ujian.daftar')}?program_tes_id=${encodeURIComponent(programTesId)}`
}

function ujianUrl(ujianId) {
    const query = new URLSearchParams({
        program_tes_id: programTesFilter.value,
        ujian_id: ujianId,
    })

    return `${route('admin.periode-ujian.daftar')}?${query.toString()}`
}

function selectUjian(ujian) {
    router.get(ujianUrl(ujian.id))
}

function openCreatePeriod() {
    creatingPeriod.value = true
    editingPeriod.value = null
    createForm.reset()
    createForm.clearErrors()
    showPeriodModal.value = true
}

function openEditPeriod(period) {
    creatingPeriod.value = false
    editingPeriod.value = period
    editForm.reset()
    editForm.clearErrors()
    editForm.judul = period.judul || ''
    editForm.tanggal_mulai = period.tanggal_mulai?.substring(0, 10) || ''
    editForm.tanggal_selesai = period.tanggal_selesai?.substring(0, 10) || ''
    editForm.aktif = !!period.aktif
    showPeriodModal.value = true
}

function closePeriodModal() {
    showPeriodModal.value = false
    creatingPeriod.value = false
    editingPeriod.value = null
    createForm.reset()
    createForm.clearErrors()
    editForm.reset()
    editForm.clearErrors()
}

function submitPeriod() {
    if (creatingPeriod.value) {
        createForm.post(route('admin.periode-ujian.simpan', props.ujianTerpilihId), {
            preserveScroll: true,
            onSuccess: closePeriodModal,
        })

        return
    }

    if (!editingPeriod.value) return

    editForm.put(route('admin.periode-ujian.perbarui', editingPeriod.value.id), {
        preserveScroll: true,
        onSuccess: closePeriodModal,
    })
}

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'
}

async function destroy(periodeUjian) {
    if (periodeUjian.slot_ujian_count > 0) return
    if (!await confirm.confirm(`Hapus periode "${periodeUjian.judul}"?`)) return
    router.delete(route('admin.periode-ujian.hapus', periodeUjian.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Penjadwalan Ujian" />
    <TataLetakDasbor
        :title="pageTitle"
        :subtitle="pageSubtitle"
        :back-href="backHref"
    >
        <template v-if="!programTesFilter">
            <ProgramTesSummaryTable
                table-key="admin-periode-ujian-program-tes"
                :ringkasan-program-tes="ringkasanProgramTes"
                :detail-url="programTesUrl"
            />
        </template>

        <ContextSummaryTable
            v-else-if="!ujianFilter"
            table-key="admin-periode-ujian-ujian"
            title="Daftar Ujian"
            :data="ringkasanUjian"
            :columns="ujianColumns"
            search-placeholder="Cari ujian..."
            @select="selectUjian"
        />

        <template v-else>
            <AppDataTable
                table-key="admin-periode-ujian"
                :data="periodeUjian.data"
                :columns="periodeColumns"
                :page-size="Number(periodeUjian.per_page) || 15"
                :page-size-options="[15, 25, 50, 100]"
                :filters="tableFilters"
                :fixed-filters="tableFixedFilters"
                server-side
                :server-total-rows="periodeUjian.total ?? 0"
                :reload-only="['periodeUjian']"
                searchable
                search-placeholder="Cari periode..."
                title="Daftar Periode Ujian"
            >
                <template #actions>
                    <BaseTambahData @click="openCreatePeriod" />
                </template>
            </AppDataTable>

            <FormPeriodeUjianModal
                :show="showPeriodModal"
                :creating="creatingPeriod"
                :form="modalForm"
                :ujian="ujianTerpilih"
                @close="closePeriodModal"
                @submit="submitPeriod"
            />
        </template>
    </TataLetakDasbor>
</template>
