<script setup>
import { computed, h, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconCheck, IconExternalLink, IconX } from '@tabler/icons-vue';
import { statusPendaftaranLabel, statusPendaftaranVariant } from '@/constants/statusPendaftaran';
import {
    jenisPesertaIdentitasLabel,
    jenisPesertaLabel,
    jenisPesertaVariant,
} from '@/constants/jenisPeserta';

const props = defineProps({
    pendaftaranUjian: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    tableFilters: { type: Array, default: () => [] },
    tableKey: { type: String, required: true },
    title: { type: String, default: 'Daftar Peserta' },
    searchPlaceholder: { type: String, default: 'Cari nama, email, atau identitas...' },
    showExamContext: { type: Boolean, default: false },
});

const confirm = useConfirm();
const showReviewModal = ref(false);
const reviewAction = ref('');
const reviewing = ref(null);
const reviewForm = useForm({ catatan_admin: '' });

const initialFilters = computed(() => {
    const keys = props.tableFilters.map(filter => filter.key);

    return Object.fromEntries(
        keys
            .filter(key => props.filters?.[key])
            .map(key => [key, props.filters[key]]),
    );
});

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

const columns = computed(() => {
    const daftarKolom = [
        {
            accessorKey: 'pengguna',
            header: 'Peserta',
            enableSorting: false,
            cell: info => {
                const pengguna = info.row.original.pengguna;
                const profil = pengguna?.profil_peserta;
                const jenis = profil?.jenis_peserta;
                const labelIdentitas = jenisPesertaIdentitasLabel[jenis] || 'Identitas';
                const children = [
                    h('p', { class: 'font-semibold text-text-heading' }, pengguna?.name || '-'),
                    h('p', { class: 'text-label-md text-text-muted' }, pengguna?.email || '-'),
                ];

                if (jenis) {
                    children.push(h(
                        'div',
                        { class: 'mt-1 flex flex-wrap items-center gap-1' },
                        [
                            h(BaseBadge, { variant: jenisPesertaVariant[jenis] || 'neutral' }, () => jenisPesertaLabel[jenis] || jenis),
                            h('span', { class: 'text-label-md text-text-muted' }, `${labelIdentitas}: ${profil?.nomor_identitas || '-'}`),
                        ],
                    ));
                } else {
                    children.push(h('p', { class: 'text-label-md text-text-muted' }, 'Identitas: -'));
                }

                return h('div', children);
            },
        },
    ];

    if (props.showExamContext) {
        daftarKolom.push({
            id: 'konteks_ujian',
            header: 'Ujian',
            enableSorting: false,
            cell: info => {
                const slot = info.row.original.slot_ujian;
                const periode = slot?.periode_ujian;
                const ujian = periode?.ujian;
                const programTes = ujian?.program_tes;

                return h('div', [
                    h('p', { class: 'font-semibold text-text-heading' }, ujian?.judul || '-'),
                    h('p', { class: 'text-label-md text-text-muted' }, programTes?.nama || '-'),
                    h(
                        'p',
                        { class: 'text-label-md text-text-muted' },
                        `${periode?.judul || '-'} • ${formatDate(slot?.tanggal)} ${formatTimeRange(slot)}`,
                    ),
                ]);
            },
        });
    } else {
        daftarKolom.push({
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
        });
    }

    daftarKolom.push(
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
                    h(BaseBadge, { variant: statusPendaftaranVariant(info.getValue()) }, () => statusPendaftaranLabel(info.getValue())),
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
    );

    return daftarKolom;
});
</script>

<template>
    <AppDataTable
        :table-key="tableKey"
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
        :search-placeholder="searchPlaceholder"
        :title="title"
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
</template>
