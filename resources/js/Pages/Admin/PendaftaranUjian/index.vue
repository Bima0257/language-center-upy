<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import FormPeriodePendaftaranModal from '@/Pages/Admin/PendaftaranUjian/Components/FormPeriodePendaftaranModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconEdit, IconEye, IconSearch, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    periodeUjian: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const showModal = ref(false);
const creating = ref(false);
const editingPeriod = ref(null);

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

const statusOptions = [
    { id: 'upcoming', nama: 'Akan Dibuka' },
    { id: 'open', nama: 'Sedang Dibuka' },
    { id: 'closed', nama: 'Ditutup' },
    { id: 'inactive', nama: 'Nonaktif' },
];

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

function filterData() {
    router.get(route('admin.pendaftaran-ujian.daftar'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    filterData();
}

async function destroy(period) {
    if (!await confirm.confirm(`Hapus periode pendaftaran "${period.nama}"?`)) return;

    router.delete(route('admin.pendaftaran-ujian.hapus', period.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Periode Pendaftaran Ujian" />
    <TataLetakDasbor title="Periode Pendaftaran Ujian" subtitle="Atur pembukaan pendaftaran dan slot peserta">
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

            <template #filters>
                <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-end">
                    <div class="relative min-w-0 flex-1">
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">Cari periode</label>
                        <IconSearch class="absolute left-4 top-[2.65rem] -translate-y-1/2 text-text-muted" :size="18" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nama periode atau ujian..."
                            class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest py-3 pl-11 pr-4 text-body-md text-text-body focus:border-secondary focus:outline-none"
                            @keyup.enter="filterData"
                        />
                    </div>
                    <div class="w-full lg:w-56">
                        <DropDown
                            v-model="status"
                            :options="statusOptions"
                            label="Status"
                            placeholder="Semua status"
                            option-label="nama"
                            option-value="id"
                            clearable
                        />
                    </div>
                    <div class="flex gap-2">
                        <BaseButton type="button" @click="filterData">Cari</BaseButton>
                        <BaseButton type="button" variant="secondary" @click="resetFilter">Reset</BaseButton>
                    </div>
                </div>
            </template>

            <template #period="{ row }">
                <p class="font-semibold text-text-heading">{{ row.nama }}</p>
                <p class="mt-1 max-w-xs text-label-md text-text-muted">{{ row.catatan || 'Tidak ada catatan.' }}</p>
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
                    size="sm"
                    class="!p-2"
                    title="Lihat detail"
                    aria-label="Lihat detail"
                >
                    <IconEye :size="16" />
                </BaseButton>
                <BaseButton
                    variant="secondary"
                    size="sm"
                    class="!p-2"
                    title="Ubah periode"
                    aria-label="Ubah periode"
                    @click="openEdit(row)"
                >
                    <IconEdit :size="16" />
                </BaseButton>
                <BaseButton
                    variant="danger"
                    size="sm"
                    class="!p-2"
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
    </TataLetakDasbor>
</template>
