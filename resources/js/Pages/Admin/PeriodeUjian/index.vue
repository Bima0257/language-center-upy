<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import Pagination from '@/Components/Shared/Pagination.vue'
import ContextSummaryTable from '@/Components/Ujian/ContextSummaryTable.vue'
import JenisTesSummaryTable from '@/Pages/Admin/PeriodeUjian/Components/JenisTesSummaryTable.vue'
import FormPeriodeUjianModal from '@/Pages/Admin/PeriodeUjian/components/FormPeriodeUjianModal.vue'
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue'
import { computed, ref } from 'vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

const props = defineProps({
    periodeUjian: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    ringkasanJenisTes: { type: Array, default: () => [] },
    ringkasanUjian: { type: Array, default: () => [] },
    jenisTesTerpilih: { type: Object, default: null },
    jenisTesTerpilihId: { type: [String, null], default: null },
    ujianTerpilih: { type: Object, default: null },
    ujianTerpilihId: { type: [String, null], default: null },
})

const jenisTesFilter = computed(() => props.jenisTesTerpilihId || '')
const ujianFilter = computed(() => props.ujianTerpilihId || '')
const tablePageSize = Number(props.periodeUjian.meta?.per_page) || 15
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
    if (props.jenisTesTerpilih) return `Daftar Ujian - ${props.jenisTesTerpilih.nama}`

    return 'Penjadwalan Ujian'
})

const pageSubtitle = computed(() => {
    if (props.ujianTerpilih) return 'Kelola periode dan slot jadwal ujian'
    if (props.jenisTesTerpilih) return 'Pilih ujian untuk melihat dan mengelola periode'

    return 'Pilih jenis tes untuk melihat daftar ujian'
})

const backHref = computed(() => {
    if (ujianFilter.value) return jenisTesUrl(jenisTesFilter.value)
    if (jenisTesFilter.value) return route('admin.periode-ujian.daftar')

    return null
})

const ujianColumns = [
    { key: 'judul', label: 'Ujian', sortable: true, className: 'font-medium text-text-heading' },
    {
        key: 'mode',
        label: 'Mode',
        sortable: true,
        badge: true,
        render: value => value === 'tryout' ? 'Try Out' : 'Ujian Resmi',
    },
    { key: 'jumlahPeriode', label: 'Periode', sortable: true, render: value => value ?? 0 },
]

const periodeColumns = [
    { key: 'judul', label: 'Periode', sortable: true, slot: 'period' },
    {
        key: 'tanggal_mulai',
        label: 'Rentang Tanggal',
        render: (_value, row) => `${formatDate(row.tanggal_mulai)} s/d ${formatDate(row.tanggal_selesai)}`,
    },
    { key: 'slot_ujian_count', label: 'Sesi', sortable: true, render: value => `${value ?? 0} sesi` },
    {
        key: 'aktif',
        label: 'Status',
        sortable: true,
        badge: true,
        render: value => value ? 'Aktif' : 'Nonaktif',
    },
    { key: 'id', label: 'Aksi', slot: 'actions' },
]

function jenisTesUrl(jenisTesId) {
    return `${route('admin.periode-ujian.daftar')}?jenis_tes_id=${encodeURIComponent(jenisTesId)}`
}

function ujianUrl(ujianId) {
    const query = new URLSearchParams({
        jenis_tes_id: jenisTesFilter.value,
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
        <template v-if="!jenisTesFilter">
            <JenisTesSummaryTable
                :ringkasan-jenis-tes="ringkasanJenisTes"
                :detail-url="jenisTesUrl"
            />
        </template>

        <ContextSummaryTable
            v-else-if="!ujianFilter"
            title="Daftar Ujian"
            :data="ringkasanUjian"
            :columns="ujianColumns"
            search-placeholder="Cari ujian..."
            @select="selectUjian"
        />

        <template v-else>
            <DataTable
                :data="periodeUjian.data"
                :columns="periodeColumns"
                :default-page-size="tablePageSize"
                :page-size-options="[tablePageSize]"
                :show-page-size="false"
                searchable
                search-placeholder="Cari periode..."
                header-title="Daftar Periode Ujian"
            >
                <template #toolbar>
                    <BaseTambahData @click="openCreatePeriod" />
                </template>

                <template #period="{ row }">
                    <p class="font-semibold text-text-heading">{{ row.judul }}</p>
                </template>

                <template #actions="{ row }">
                    <BaseButton
                        :href="route('admin.periode-ujian.detail', row.id)"
                        variant="secondary"
                        icon-only
                        title="Kelola sesi"
                        aria-label="Kelola sesi"
                    >
                        <IconEye :size="16" />
                    </BaseButton>
                    <BaseButton
                        variant="secondary"
                        icon-only
                        title="Ubah periode"
                        aria-label="Ubah periode"
                        @click="openEditPeriod(row)"
                    >
                        <IconEdit :size="16" />
                    </BaseButton>
                    <BaseButton
                        variant="danger"
                        icon-only
                        :disabled="row.slot_ujian_count > 0"
                        :title="row.slot_ujian_count > 0 ? 'Tidak bisa dihapus — sudah punya sesi' : 'Hapus periode'"
                        :aria-label="row.slot_ujian_count > 0 ? 'Tidak bisa dihapus — sudah punya sesi' : 'Hapus periode'"
                        @click="destroy(row)"
                    >
                        <IconTrash :size="16" />
                    </BaseButton>
                </template>
            </DataTable>

            <Pagination
                :links="periodeUjian.links"
                :total="periodeUjian.meta?.total ?? 0"
                :per-page="periodeUjian.meta?.per_page ?? 0"
            />

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
