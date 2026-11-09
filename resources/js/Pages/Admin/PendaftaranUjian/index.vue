<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import FormPeriodePendaftaranModal from '@/Pages/Admin/PendaftaranUjian/Components/FormPeriodePendaftaranModal.vue';
import JenisTesSummaryTable from '@/Pages/Admin/PendaftaranUjian/Components/JenisTesSummaryTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    periodeUjian: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    ringkasanJenisTes: { type: Array, default: () => [] },
    jenisTesTerpilih: { type: Object, default: null },
    jenisTesTerpilihId: { type: [String, null], default: null },
});

const confirm = useConfirm();
const showModal = ref(false);
const creating = ref(false);
const editingPeriod = ref(null);
const jenisTesFilter = computed(() => props.jenisTesTerpilihId || '');

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
    { key: 'nama', label: 'Periode', sortable: true, slot: 'period' },
    { key: 'periode_ujian', label: 'Ujian', slot: 'exam' },
    {
        key: 'dibuka_pada',
        label: 'Pendaftaran',
        sortable: true,
        render: (_value, row) => `${formatDateTime(row.dibuka_pada)} s/d ${formatDateTime(row.ditutup_pada)}`,
    },
    { key: 'slot_ujian_count', label: 'Slot', sortable: true, render: (value) => `${value || 0} slot` },
    { key: 'status_pendaftaran', label: 'Status', slot: 'status' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

const tablePageSize = Number(props.periodePendaftaranUjian.per_page) || 15;

const modalForm = computed(() => (creating.value ? form : editForm));

function jenisTesUrl(jenisTesId) {
    return `${route('admin.pendaftaran-ujian.daftar')}?jenis_tes_id=${encodeURIComponent(jenisTesId)}`;
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
    creating.value = false;
    editingPeriod.value = null;
    form.clearErrors();
    editForm.clearErrors();
    form.reset();
    editForm.reset();
}

async function destroy(period) {
    if (!await confirm.confirm(`Hapus periode pendaftaran "${period.nama}"?`)) return;

    router.delete(route('admin.pendaftaran-ujian.hapus', period.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Periode Pendaftaran Ujian" />
    <TataLetakDasbor
        :title="jenisTesTerpilih ? `Periode Pendaftaran - ${jenisTesTerpilih.nama}` : 'Periode Pendaftaran Ujian'"
        :subtitle="jenisTesTerpilih ? 'Atur pembukaan pendaftaran dan slot peserta' : 'Pilih jenis tes untuk melihat periode pendaftaran'"
        :back-href="jenisTesFilter ? route('admin.pendaftaran-ujian.daftar') : null"
    >
        <template v-if="!jenisTesFilter">
            <JenisTesSummaryTable
                :ringkasan-jenis-tes="ringkasanJenisTes"
                :detail-url="jenisTesUrl"
            />
        </template>

        <template v-else>
            <DataTable
                :data="periodePendaftaranUjian.data"
                :columns="columns"
                :default-page-size="tablePageSize"
                :page-size-options="[tablePageSize]"
                :show-page-size="false"
                header-title="Daftar Periode Pendaftaran"
            >
            <template #toolbar>
                <BaseTambahData @click="openCreate" />
            </template>

            <template #period="{ row }">
                <p class="font-semibold text-text-heading">{{ row.nama }}</p>
            </template>

            <template #exam="{ row }">
                <p class="font-medium text-text-heading">{{ row.periode_ujian?.ujian?.jenis_tes?.nama || '-' }}</p>
                <p class="text-label-md text-text-muted">{{ row.periode_ujian?.ujian?.judul || '-' }}</p>
                <p class="text-label-md text-text-muted">{{ row.periode_ujian?.judul || '-' }}</p>
            </template>

            <template #status="{ row }">
                <span class="inline-flex rounded-full px-3 py-1 text-label-md font-medium" :class="statusClass(row.status_pendaftaran)">
                    {{ statusLabel(row.status_pendaftaran) }}
                </span>
            </template>

            <template #actions="{ row }">
                <BaseButton
                    :href="route('admin.pendaftaran-ujian.detail', row.id)"
                    variant="secondary"
                    icon-only
                    title="Lihat detail"
                    aria-label="Lihat detail"
                >
                    <IconEye :size="16" />
                </BaseButton>
                <BaseButton
                    variant="secondary"
                    icon-only
                    title="Ubah periode"
                    aria-label="Ubah periode"
                    @click="openEdit(row)"
                >
                    <IconEdit :size="16" />
                </BaseButton>
                <BaseButton
                    variant="danger"
                    icon-only
                    title="Hapus periode"
                    aria-label="Hapus periode"
                    @click="destroy(row)"
                >
                    <IconTrash :size="16" />
                </BaseButton>
            </template>
            </DataTable>

            <Pagination
                :links="periodePendaftaranUjian.links"
                :total="periodePendaftaranUjian.total"
                :per-page="periodePendaftaranUjian.per_page"
            />

            <FormPeriodePendaftaranModal
                :show="showModal"
                :creating="creating"
                :form="modalForm"
                :periode-ujian="periodeUjian"
                @close="closeModal"
                @submit="submit"
            />
        </template>
    </TataLetakDasbor>
</template>
