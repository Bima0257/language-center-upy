<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, h, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconCheck, IconExternalLink, IconX } from '@tabler/icons-vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, required: true },
    slotUjian: { type: Array, default: () => [] },
    pendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const showReviewModal = ref(false);
const reviewAction = ref('');
const reviewing = ref(null);
const reviewForm = useForm({ catatan_admin: '' });

const statusOptions = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

const tableFilters = [
    { key: 'status', label: 'Status', options: statusOptions },
];

const initialFilters = computed(() => (
    props.filters.status ? { status: props.filters.status } : {}
));

function formatDate(value) {
    return value
        ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
        : '-';
}

function formatTimeRange(slot) {
    const mulai = slot?.waktu_mulai?.substring(0, 5) || '-';
    const selesai = slot?.waktu_selesai?.substring(0, 5) || '-';

    return `${mulai} - ${selesai}`;
}

function statusLabel(value) {
    return statusOptions.find(option => option.value === value)?.label || value;
}

function statusVariant(value) {
    return {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'neutral',
    }[value] || 'neutral';
}

function evidenceUrl(path) {
    return path ? `/media/${path}` : null;
}

function openReview(pendaftaran, action) {
    reviewing.value = pendaftaran;
    reviewAction.value = action;
    reviewForm.reset();
    reviewForm.clearErrors();
    showReviewModal.value = true;
}

function closeReview() {
    showReviewModal.value = false;
    reviewing.value = null;
    reviewAction.value = '';
    reviewForm.reset();
    reviewForm.clearErrors();
}

function submitReview() {
    const routeName = `admin.pendaftaran-ujian.${reviewAction.value}`;
    reviewForm.patch(route(routeName, reviewing.value.id), {
        preserveScroll: true,
        onSuccess: closeReview,
    });
}

async function cancelDirectly(pendaftaran) {
    if (!await confirm.confirm(`Batalkan pendaftaran ${pendaftaran.pengguna?.name || ''}?`)) return;

    router.patch(route('admin.pendaftaran-ujian.batalkan', pendaftaran.id), {}, { preserveScroll: true });
}

const columns = [
    {
        accessorKey: 'pengguna',
        header: 'Peserta',
        enableSorting: false,
        cell: info => {
            const pengguna = info.row.original.pengguna;

            return h('div', [
                h('p', { class: 'font-semibold text-text-heading' }, pengguna?.name || '-'),
                h('p', { class: 'text-label-md text-text-muted' }, pengguna?.email || '-'),
                h('p', { class: 'text-label-md text-text-muted' }, `NIM: ${pengguna?.profil_mahasiswa?.nim || '-'}`),
            ]);
        },
    },
    {
        accessorKey: 'slot_ujian',
        header: 'Slot',
        enableSorting: false,
        cell: info => {
            const slot = info.row.original.slot_ujian;

            return h('div', [
                h('p', { class: 'text-body-md text-text-body' }, formatDate(slot?.tanggal)),
                h('p', { class: 'text-label-md text-text-muted' }, formatTimeRange(slot)),
            ]);
        },
    },
    {
        accessorKey: 'lokasi_bukti_pembayaran',
        header: 'Bukti Pembayaran',
        enableSorting: false,
        cell: info => {
            const url = evidenceUrl(info.getValue());

            if (!url) {
                return h('span', { class: 'text-label-md text-text-muted' }, 'Tidak diperlukan');
            }

            return h(
                'a',
                {
                    href: url,
                    target: '_blank',
                    rel: 'noopener',
                    class: 'inline-flex items-center gap-1 text-label-md font-semibold text-secondary hover:underline',
                },
                ['Lihat file', h(IconExternalLink, { size: 14 })],
            );
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        enableSorting: false,
        cell: info => {
            const pendaftaran = info.row.original;
            const children = [
                h(BaseBadge, { variant: statusVariant(info.getValue()) }, () => statusLabel(info.getValue())),
            ];

            if (pendaftaran.catatan_admin) {
                children.push(h('p', { class: 'mt-2 max-w-xs text-label-md text-text-muted' }, pendaftaran.catatan_admin));
            }

            return h('div', children);
        },
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => {
            const pendaftaran = info.row.original;

            if (pendaftaran.status === 'pending') {
                return h('div', { class: 'flex flex-wrap items-center justify-center gap-1' }, [
                    h(
                        BaseButton,
                        { variant: 'success', size: 'xs', onClick: () => openReview(pendaftaran, 'setujui') },
                        () => [h(IconCheck, { size: 14 }), 'Setujui'],
                    ),
                    h(
                        BaseButton,
                        { variant: 'danger', size: 'xs', onClick: () => openReview(pendaftaran, 'tolak') },
                        () => [h(IconX, { size: 14 }), 'Tolak'],
                    ),
                ]);
            }

            if (pendaftaran.status === 'approved') {
                return h(
                    'button',
                    {
                        type: 'button',
                        class: 'text-label-md font-semibold text-error-red hover:underline',
                        onClick: () => cancelDirectly(pendaftaran),
                    },
                    'Batalkan',
                );
            }

            return h('span', { class: 'text-label-md text-text-muted' }, '-');
        },
    },
];
</script>

<template>
    <Head title="Peserta Pendaftaran Ujian" />
    <TataLetakDasbor
        title="Peserta Pendaftaran Ujian"
        :subtitle="periodePendaftaranUjian.nama"
        :back-href="route('admin.pendaftaran-ujian.detail', periodePendaftaranUjian.id)"
        back-label="Detail Periode"
    >
        <AppDataTable
            :table-key="`admin-pendaftaran-ujian-peserta-${periodePendaftaranUjian.id}`"
            :data="pendaftaranUjian.data"
            :columns="columns"
            :page-size="Number(pendaftaranUjian.per_page) || 15"
            :page-size-options="[15, 25, 50, 100]"
            :filters="tableFilters"
            :initial-filters="initialFilters"
            server-side
            :server-total-rows="pendaftaranUjian.total ?? 0"
            :reload-only="['pendaftaranUjian', 'filters']"
            searchable
            search-placeholder="Cari nama, email, atau NIM..."
            title="Daftar Peserta"
        />

        <BaseModal
            :show="showReviewModal"
            max-width="md"
            fit-content
            aria-labelledby="registration-review-title"
            aria-describedby="registration-review-description"
            @close="closeReview"
        >
            <template #header>
                <BaseModalHeader>
                    <div class="px-6 py-4">
                        <h2 id="registration-review-title" class="text-title-lg font-semibold text-text-heading">
                            {{ reviewAction === 'setujui' ? 'Setujui pendaftaran' : 'Tolak pendaftaran' }}
                        </h2>
                        <p id="registration-review-description" class="mt-1 text-body-md text-text-muted">
                            {{ reviewing?.pengguna?.name }}
                        </p>
                    </div>
                </BaseModalHeader>
            </template>

            <template #default>
                <form id="registration-review-form" class="px-6 pb-6 pt-5" @submit.prevent="submitReview">
                    <BaseFormGrid>
                        <div class="sm:col-span-2">
                            <label class="block text-label-md font-medium text-text-heading">Catatan admin</label>
                            <textarea v-model="reviewForm.catatan_admin" rows="4" class="mt-1.5 w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading" placeholder="Opsional"></textarea>
                            <p v-if="reviewForm.errors.catatan_admin" class="mt-1 text-xs text-error-red">{{ reviewForm.errors.catatan_admin }}</p>
                        </div>
                    </BaseFormGrid>
                </form>
            </template>

            <template #footer>
                <BaseModalFooter>
                    <div class="px-6 pb-6">
                        <BaseFormActions
                            form="registration-review-form"
                            :processing="reviewForm.processing"
                            @cancel="closeReview"
                        />
                    </div>
                </BaseModalFooter>
            </template>
        </BaseModal>
    </TataLetakDasbor>
</template>
