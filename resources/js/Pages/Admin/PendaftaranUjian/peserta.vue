<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconCheck, IconExternalLink, IconSearch, IconX } from '@tabler/icons-vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, required: true },
    slotUjian: { type: Array, default: () => [] },
    pendaftaranUjian: { type: Object, default: () => ({ data: [], links: [], total: 0, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const slotId = ref(props.filters.slot_ujian_id || '');
const showReviewModal = ref(false);
const reviewAction = ref('');
const reviewing = ref(null);
const reviewForm = useForm({ catatan_admin: '' });

const statusOptions = [
    { id: 'pending', nama: 'Menunggu' },
    { id: 'approved', nama: 'Disetujui' },
    { id: 'rejected', nama: 'Ditolak' },
    { id: 'cancelled', nama: 'Dibatalkan' },
];

function slotLabel(slot) {
    const date = slot.tanggal ? new Date(slot.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    return `${date} ${slot.waktu_mulai?.substring(0, 5) || ''} - ${slot.waktu_selesai?.substring(0, 5) || ''}`;
}

function pesertaUrl() {
    const query = new URLSearchParams();
    const params = { search: search.value, status: status.value, slot_ujian_id: slotId.value };

    Object.entries(params).forEach(([key, value]) => {
        if (value) query.set(key, value);
    });

    const baseUrl = route('admin.pendaftaran-ujian.peserta', props.periodePendaftaranUjian.id);
    const queryString = query.toString();

    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
}

function filterData() {
    router.get(pesertaUrl(), {}, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    slotId.value = '';
    filterData();
}

function statusLabel(value) {
    return {
        pending: 'Menunggu',
        approved: 'Disetujui',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
    }[value] || value;
}

function statusClass(value) {
    return {
        pending: 'bg-warning-container text-on-warning-container',
        approved: 'bg-success-container text-on-success-container',
        rejected: 'bg-error-container text-on-error-container',
        cancelled: 'bg-neutral-container text-on-neutral-container',
    }[value] || 'bg-neutral-container text-on-neutral-container';
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

function evidenceUrl(path) {
    return path ? `/media/${path}` : null;
}
</script>

<template>
    <Head title="Peserta Pendaftaran Ujian" />
    <TataLetakDasbor
        title="Peserta Pendaftaran Ujian"
        :subtitle="periodePendaftaranUjian.nama"
        :back-href="route('admin.pendaftaran-ujian.detail', periodePendaftaranUjian.id)"
        back-label="Detail Periode"
    >
        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-outline-variant/30 bg-surface-container-low/30 px-5 py-4 lg:flex-row lg:items-end">
            <div class="relative flex-1">
                <label class="mb-1.5 block text-label-md font-medium text-text-heading">Cari peserta</label>
                <IconSearch class="absolute left-4 top-[2.65rem] -translate-y-1/2 text-text-muted" :size="18" />
                <input v-model="search" type="text" placeholder="Nama, email, atau NIM..." class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest py-3 pl-11 pr-4 text-body-md text-text-body focus:border-secondary focus:outline-none" @keyup.enter="filterData" />
            </div>
            <div class="w-full lg:w-72">
                <DropDown v-model="slotId" :options="slotUjian" label="Slot Peserta" placeholder="Semua slot" :option-label="slotLabel" option-value="id" clearable />
            </div>
            <div class="w-full lg:w-52">
                <DropDown v-model="status" :options="statusOptions" label="Status" placeholder="Semua status" option-label="nama" option-value="id" clearable />
            </div>
            <div class="flex gap-2">
                <BaseButton type="button" @click="filterData">Cari</BaseButton>
                <BaseButton type="button" variant="secondary" @click="resetFilter">Reset</BaseButton>
            </div>
        </div>

        <BaseCard :padding="false" class="overflow-hidden">
            <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
                <h2 class="text-title-lg font-semibold text-text-heading">Daftar Peserta</h2>
                <p class="mt-1 text-body-md text-text-muted">Verifikasi bukti pembayaran dan status peserta pada periode ini.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Peserta</th>
                            <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Slot</th>
                            <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Bukti Pembayaran</th>
                            <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Status</th>
                            <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pendaftaran in pendaftaranUjian.data" :key="pendaftaran.id" class="border-b border-outline-variant/20 align-top last:border-0 hover:bg-surface-container-low/50">
                            <td class="px-5 py-4">
                                <p class="text-body-md font-semibold text-text-heading">{{ pendaftaran.pengguna?.name }}</p>
                                <p class="text-label-md text-text-muted">{{ pendaftaran.pengguna?.email }}</p>
                                <p class="text-label-md text-text-muted">NIM: {{ pendaftaran.pengguna?.profil_mahasiswa?.nim || '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-body-md text-text-body">
                                <p>{{ pendaftaran.slot_ujian?.tanggal }}</p>
                                <p class="text-label-md text-text-muted">{{ pendaftaran.slot_ujian?.waktu_mulai?.substring(0, 5) }} - {{ pendaftaran.slot_ujian?.waktu_selesai?.substring(0, 5) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <a v-if="evidenceUrl(pendaftaran.lokasi_bukti_pembayaran)" :href="evidenceUrl(pendaftaran.lokasi_bukti_pembayaran)" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-label-md font-semibold text-secondary hover:underline">
                                    Lihat file <IconExternalLink :size="14" />
                                </a>
                                <span v-else class="text-label-md text-text-muted">Tidak diperlukan</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-label-md font-medium" :class="statusClass(pendaftaran.status)">{{ statusLabel(pendaftaran.status) }}</span>
                                <p v-if="pendaftaran.catatan_admin" class="mt-2 max-w-xs text-label-md text-text-muted">{{ pendaftaran.catatan_admin }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div v-if="pendaftaran.status === 'pending'" class="flex flex-wrap gap-2">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-full bg-success px-3 py-2 text-label-md font-semibold text-on-success hover:opacity-90" @click="openReview(pendaftaran, 'setujui')"><IconCheck :size="14" /> Setujui</button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-full bg-error-red px-3 py-2 text-label-md font-semibold text-on-error hover:opacity-90" @click="openReview(pendaftaran, 'tolak')"><IconX :size="14" /> Tolak</button>
                                </div>
                                <button v-else-if="pendaftaran.status === 'approved'" type="button" class="text-label-md font-semibold text-error-red hover:underline" @click="cancelDirectly(pendaftaran)">Batalkan</button>
                                <span v-else class="text-label-md text-text-muted">-</span>
                            </td>
                        </tr>
                        <tr v-if="pendaftaranUjian.data?.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-body-md text-text-muted">Belum ada peserta sesuai filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>

        <Pagination :links="pendaftaranUjian.links" :total="pendaftaranUjian.total" :per-page="pendaftaranUjian.per_page" />

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
