<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, h, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import FormPeriodePendaftaranModal from '@/Pages/Admin/PendaftaranUjian/Components/FormPeriodePendaftaranModal.vue';
import DetailPeriodePendaftaranModal from '@/Pages/Admin/PendaftaranUjian/Components/DetailPeriodePendaftaranModal.vue';
import ProgramTesSummaryTable from '@/Pages/Admin/PendaftaranUjian/Components/ProgramTesSummaryTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    periodeUjian: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    ringkasanProgramTes: { type: Array, default: () => [] },
    programTesTerpilih: { type: Object, default: null },
    programTesTerpilihId: { type: [String, null], default: null },
});

const confirm = useConfirm();
const showModal = ref(false);
const creating = ref(false);
const editingPeriod = ref(null);
const showDetailModal = ref(false);
const detailPeriod = ref(null);
const programTesFilter = computed(() => props.programTesTerpilihId || '');

const form = useForm({
    periode_ujian_id: '',
    nama: '',
    dibuka_pada: '',
    ditutup_pada: '',
    aktif: true,
    catatan: '',
});

const editForm = useForm({
    nama: '',
    dibuka_pada: '',
    ditutup_pada: '',
    aktif: true,
    catatan: '',
});

const tableFilters = [
    {
        key: 'status',
        label: 'Status',
        options: [
            { value: 'upcoming', label: 'Akan Dibuka' },
            { value: 'open', label: 'Sedang Dibuka' },
            { value: 'closed', label: 'Ditutup' },
            { value: 'inactive', label: 'Nonaktif' },
        ],
    },
];

const tableFixedFilters = computed(() => (
    programTesFilter.value ? { program_tes_id: programTesFilter.value } : {}
));

const tableInitialFilters = computed(() => (
    props.filters.status ? { status: props.filters.status } : {}
));

function formatDateTime(value) {
    return value
        ? new Date(value).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        })
        : '-';
}

function statusLabel(value) {
    return {
        upcoming: 'Akan Dibuka',
        open: 'Sedang Dibuka',
        closed: 'Ditutup',
        inactive: 'Nonaktif',
    }[value] || value;
}

function statusClass(value) {
    return {
        upcoming: 'bg-info-container text-on-info-container',
        open: 'bg-success-container text-on-success-container',
        closed: 'bg-warning-container text-on-warning-container',
        inactive: 'bg-neutral-container text-on-neutral-container',
    }[value] || 'bg-neutral-container text-on-neutral-container';
}

const columns = [
    {
        accessorKey: 'nama',
        header: 'Periode',
        cell: info => h('p', { class: 'font-semibold text-text-heading' }, info.getValue()),
    },
    {
        accessorKey: 'periode_ujian',
        header: 'Ujian',
        enableSorting: false,
        cell: info => h('div', [
            h('p', { class: 'text-label-md text-text-muted' }, info.row.original.periode_ujian?.ujian?.judul || '-'),
            h('p', { class: 'text-label-md text-text-muted' }, info.row.original.periode_ujian?.judul || '-'),
        ]),
    },
    {
        accessorKey: 'dibuka_pada',
        header: 'Pendaftaran',
        cell: info => `${formatDateTime(info.row.original.dibuka_pada)} s/d ${formatDateTime(info.row.original.ditutup_pada)}`,
    },
    {
        accessorKey: 'slot_ujian_count',
        header: 'Slot',
        cell: info => `${info.getValue() || 0} slot`,
    },
    {
        accessorKey: 'status_pendaftaran',
        header: 'Status',
        enableSorting: false,
        cell: info => h(
            'span',
            {
                class: `inline-flex rounded-full px-3 py-1 text-label-md font-medium ${statusClass(info.getValue())}`,
            },
            statusLabel(info.getValue()),
        ),
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => {
            const row = info.row.original;

            return h('div', { class: 'flex items-center justify-center gap-1' }, [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Lihat detail',
                        'aria-label': 'Lihat detail',
                        onClick: () => openDetail(row),
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
                        onClick: () => openEdit(row),
                    },
                    () => h(IconEdit, { size: 16 }),
                ),
                h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        title: 'Hapus periode',
                        'aria-label': 'Hapus periode',
                        onClick: () => destroy(row),
                    },
                    () => h(IconTrash, { size: 16 }),
                ),
            ]);
        },
    },
];

const modalForm = computed(() => (creating.value ? form : editForm));

function programTesUrl(programTesId) {
    return `${route('admin.pendaftaran-ujian.daftar')}?program_tes_id=${encodeURIComponent(programTesId)}`;
}

function localDateTime(value) {
    return value ? value.substring(0, 16).replace(' ', 'T') : '';
}

function openCreate() {
    creating.value = true;
    editingPeriod.value = null;
    form.clearErrors();
    form.reset();
    showModal.value = true;
}

function openDetail(period) {
    detailPeriod.value = period;
    showDetailModal.value = true;
}

function closeDetail() {
    showDetailModal.value = false;
}

function editFromDetail() {
    const period = detailPeriod.value;

    closeDetail();

    if (period) {
        openEdit(period);
    }
}

function openEdit(period) {
    creating.value = false;
    editingPeriod.value = period;
    editForm.clearErrors();
    editForm.reset();
    editForm.nama = period.nama || '';
    editForm.dibuka_pada = localDateTime(period.dibuka_pada);
    editForm.ditutup_pada = localDateTime(period.ditutup_pada);
    editForm.aktif = !!period.aktif;
    editForm.catatan = period.catatan || '';
    showModal.value = true;
}

function submit() {
    if (creating.value) {
        form.post(route('admin.pendaftaran-ujian.simpan'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });

        return;
    }

    editForm.put(route('admin.pendaftaran-ujian.perbarui', editingPeriod.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
}

async function destroy(period) {
    if (!await confirm.confirm(`Hapus periode pendaftaran "${period.nama}"?`)) return;

    router.delete(route('admin.pendaftaran-ujian.hapus', period.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Periode Pendaftaran Ujian" />
    <TataLetakDasbor
        :title="programTesTerpilih ? `Periode Pendaftaran - ${programTesTerpilih.nama}` : 'Periode Pendaftaran Ujian'"
        :subtitle="programTesTerpilih ? 'Atur pembukaan pendaftaran dan slot peserta' : 'Pilih program tes untuk melihat periode pendaftaran'"
        :back-href="programTesFilter ? route('admin.pendaftaran-ujian.daftar') : null"
    >
        <template v-if="!programTesFilter">
            <ProgramTesSummaryTable
                table-key="admin-pendaftaran-ujian-program-tes"
                :ringkasan-program-tes="ringkasanProgramTes"
                :detail-url="programTesUrl"
            />
        </template>

        <template v-else>
            <AppDataTable
                table-key="admin-pendaftaran-ujian"
                :data="periodePendaftaranUjian.data"
                :columns="columns"
                :page-size="Number(periodePendaftaranUjian.per_page) || 15"
                :page-size-options="[15, 25, 50, 100]"
                :filters="tableFilters"
                :initial-filters="tableInitialFilters"
                :fixed-filters="tableFixedFilters"
                server-side
                :server-total-rows="periodePendaftaranUjian.total ?? 0"
                :reload-only="['periodePendaftaranUjian', 'filters']"
                :row-click="openDetail"
                searchable
                search-placeholder="Cari periode atau ujian..."
                title="Daftar Periode Pendaftaran"
            >
                <template #actions>
                    <BaseTambahData @click="openCreate" />
                </template>
            </AppDataTable>

            <FormPeriodePendaftaranModal
                :show="showModal"
                :creating="creating"
                :form="modalForm"
                :periode-ujian="periodeUjian"
                @close="closeModal"
                @submit="submit"
            />

            <DetailPeriodePendaftaranModal
                :show="showDetailModal"
                :periode="detailPeriod"
                @close="closeDetail"
                @edit="editFromDetail"
            />
        </template>
    </TataLetakDasbor>
</template>
