<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import ContextSummaryTable from '@/Components/Ujian/ContextSummaryTable.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconCalendarEvent, IconClipboardCheck, IconClock, IconUpload, IconUsers } from '@tabler/icons-vue';

const props = defineProps({
    periodeUjian: { type: Array, default: () => [] },
    ringkasanJenisTes: { type: Array, default: () => [] },
    ringkasanUjian: { type: Array, default: () => [] },
    ringkasanPeriode: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    profil: { type: Object, default: null },
    fakultas: { type: Array, default: () => [] },
    programStudi: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const showRegistrationModal = ref(false);
const selectedSlot = ref(null);

const registrationForm = useForm({
    nim: '',
    fakultas_id: '',
    program_studi_id: '',
    tahun_angkatan: '',
    bukti_pembayaran: null,
});

const context = computed(() => props.filters || {});

const jenisTesColumns = [
    { key: 'nama', label: 'Jenis Tes', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'jumlahUjian', label: 'Ujian', sortable: true },
    { key: 'jumlahPeriode', label: 'Periode', sortable: true },
    { key: 'jumlahSlot', label: 'Slot Tersedia', sortable: true },
];

const ujianColumns = [
    { key: 'judul', label: 'Ujian', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'jumlahPeriode', label: 'Periode', sortable: true },
    { key: 'jumlahSlot', label: 'Slot Tersedia', sortable: true },
];

const periodeColumns = [
    { key: 'judul', label: 'Periode', sortable: true, className: 'font-medium text-text-heading' },
    {
        key: 'tanggalMulai',
        label: 'Tanggal',
        sortable: true,
        render: (_value, row) => `${formatDate(row.tanggalMulai)} - ${formatDate(row.tanggalSelesai)}`,
    },
    { key: 'jumlahSlot', label: 'Slot Tersedia', sortable: true },
];

const pageBackUrl = computed(() => {
    const current = context.value;

    if (current.periode_ujian_id) {
        return contextUrl({
            jenis_tes_id: current.jenis_tes_id,
            ujian_id: current.ujian_id,
        });
    }

    if (current.ujian_id) {
        return contextUrl({ jenis_tes_id: current.jenis_tes_id });
    }

    if (current.jenis_tes_id) return contextUrl();

    return null;
});

function formatDate(date) {
    return date
        ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
        : '-';
}

function formatTime(time) {
    if (!time) return '-';
    return time.substring(0, 5);
}

function contextUrl(parameters = {}) {
    const query = new URLSearchParams();

    Object.entries(parameters).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') query.set(key, value);
    });

    const baseUrl = route('ujian.jadwal');
    const queryString = query.toString();

    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
}

function navigate(parameters) {
    router.get(contextUrl(parameters), {}, { preserveState: true, replace: true });
}

function selectJenisTes(row) {
    navigate({ jenis_tes_id: row.id });
}

function selectUjian(row) {
    navigate({ jenis_tes_id: context.value.jenis_tes_id, ujian_id: row.id });
}

function selectPeriode(row) {
    navigate({
        jenis_tes_id: context.value.jenis_tes_id,
        ujian_id: context.value.ujian_id,
        periode_ujian_id: row.id,
    });
}

function slotStatus(slotUjian) {
    const now = new Date();
    const slotDate = new Date(slotUjian.tanggal);
    const [hours, minutes] = (slotUjian.waktu_mulai || '00:00').split(':').map(Number);
    slotDate.setHours(hours, minutes, 0, 0);

    const endSlot = new Date(slotDate);
    const [endHours, endMinutes] = (slotUjian.waktu_selesai || '23:59').split(':').map(Number);
    endSlot.setHours(endHours, endMinutes, 0, 0);

    if (now >= slotDate && now <= endSlot) return 'active';
    if (now > endSlot) return 'past';
    return 'upcoming';
}

function slotStatusLabel(slotUjian) {
    return { active: 'Berlangsung', past: 'Selesai', upcoming: 'Akan Datang' }[slotStatus(slotUjian)];
}

function registrationLabel(status) {
    return {
        pending: 'Menunggu verifikasi',
        approved: 'Terdaftar',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
    }[status] || '';
}

function registrationClass(status) {
    return {
        pending: 'bg-warning-container text-on-warning-container',
        approved: 'bg-success-container text-on-success-container',
        rejected: 'bg-error-container text-on-error-container',
        cancelled: 'bg-neutral-container text-on-neutral-container',
    }[status] || '';
}

function registrationWindowLabel(status) {
    return {
        upcoming: 'Pendaftaran akan dibuka',
        open: 'Pendaftaran dibuka',
        closed: 'Pendaftaran ditutup',
        inactive: 'Pendaftaran nonaktif',
    }[status] || 'Pendaftaran belum tersedia';
}

function registrationWindowClass(status) {
    return {
        upcoming: 'bg-info-container text-on-info-container',
        open: 'bg-success-container text-on-success-container',
        closed: 'bg-warning-container text-on-warning-container',
        inactive: 'bg-neutral-container text-on-neutral-container',
    }[status] || 'bg-neutral-container text-on-neutral-container';
}

function canRegister(slotUjian) {
    return slotStatus(slotUjian) === 'upcoming'
        && slotUjian.pendaftaran_terbuka === true
        && ['pending', 'approved'].includes(slotUjian.pendaftaran_status) === false;
}

function canCancel(slotUjian) {
    return slotStatus(slotUjian) === 'upcoming'
        && ['pending', 'approved'].includes(slotUjian.pendaftaran_status);
}

function openRegistration(slotUjian) {
    selectedSlot.value = slotUjian;
    registrationForm.clearErrors();
    registrationForm.reset();
    registrationForm.nim = props.profil?.nim || '';
    registrationForm.fakultas_id = props.profil?.fakultas_id || '';
    registrationForm.program_studi_id = props.profil?.program_studi_id || '';
    registrationForm.tahun_angkatan = props.profil?.tahun_angkatan || '';
    showRegistrationModal.value = true;
}

function closeRegistration() {
    showRegistrationModal.value = false;
    selectedSlot.value = null;
    registrationForm.reset();
    registrationForm.clearErrors();
}

function selectPaymentProof(event) {
    registrationForm.bukti_pembayaran = event.target.files[0] || null;
    event.target.value = '';
}

function submitRegistration() {
    registrationForm.post(route('ujian.pendaftaran.ajukan', selectedSlot.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: closeRegistration,
    });
}

async function cancelRegistration(slotUjian) {
    if (!await confirm.confirm('Batalkan pendaftaran pada slot ini?')) return;

    const registration = props.periodeUjian
        .flatMap(period => period.slot_ujian || [])
        .find(slot => slot.id === slotUjian.id);

    if (!registration?.pendaftaran_status) return;

    router.delete(route('ujian.pendaftaran.batalkan', registration.pendaftaran_id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Jadwal Ujian" />
    <TataLetakDasbor
        title="Jadwal Ujian"
        subtitle="Pilih konteks ujian untuk melihat slot yang tersedia"
        :back-href="pageBackUrl"
    >
        <ContextSummaryTable
            v-if="!context.jenis_tes_id"
            title="Daftar Jenis Tes"
            :data="ringkasanJenisTes"
            :columns="jenisTesColumns"
            search-placeholder="Cari jenis tes..."
            @select="selectJenisTes"
        />

        <ContextSummaryTable
            v-else-if="!context.ujian_id"
            title="Daftar Ujian"
            :data="ringkasanUjian"
            :columns="ujianColumns"
            search-placeholder="Cari ujian..."
            @select="selectUjian"
        />

        <ContextSummaryTable
            v-else-if="!context.periode_ujian_id"
            title="Daftar Periode Ujian"
            :data="ringkasanPeriode"
            :columns="periodeColumns"
            search-placeholder="Cari periode..."
            @select="selectPeriode"
        />

        <template v-else-if="periodeUjian.length > 0">
            <BaseCard v-for="periode in periodeUjian" :key="periode.id">
                <div class="mb-4 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-title-lg font-bold text-text-heading">{{ periode.judul }}</h2>
                        <p class="mt-1 text-body-md text-text-muted">{{ periode.ujian?.judul || '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2 text-label-md text-text-muted">
                        <IconCalendarEvent :size="16" />
                        <span>{{ formatDate(periode.tanggal_mulai) }} - {{ formatDate(periode.tanggal_selesai) }}</span>
                    </div>
                </div>
                <span class="mb-4 inline-flex rounded-full px-3 py-1 text-label-md font-medium" :class="registrationWindowClass(periode.status_pendaftaran)">
                    {{ registrationWindowLabel(periode.status_pendaftaran) }}
                </span>

                <div v-if="periode.slot_ujian?.length" class="divide-y divide-outline-variant/20">
                    <div
                        v-for="slotUjian in periode.slot_ujian"
                        :key="slotUjian.id"
                        class="flex flex-col justify-between gap-4 py-4 md:flex-row md:items-center"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-surface-container-low">
                                <IconClock class="text-secondary" :size="20" />
                            </div>
                            <div>
                                <p class="font-medium text-text-heading">
                                    {{ new Date(slotUjian.tanggal).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </p>
                                <p class="text-label-md text-text-muted">
                                    {{ formatTime(slotUjian.waktu_mulai) }} - {{ formatTime(slotUjian.waktu_selesai) }}
                                    · Toleransi {{ slotUjian.toleransi_keterlambatan_menit }} menit
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            <div class="flex items-center gap-1.5 text-label-md text-text-muted">
                                <IconUsers :size="14" />
                                <span>{{ slotUjian.pendaftaran_disetujui_count || 0 }}/{{ slotUjian.kapasitas_peserta }}</span>
                            </div>
                            <span
                                class="rounded-full px-3 py-1 text-label-md font-medium"
                                :class="{
                                    'bg-success-container text-on-success-container': slotStatus(slotUjian) === 'active',
                                    'bg-neutral-container text-on-neutral-container': slotStatus(slotUjian) === 'past',
                                    'bg-info-container text-on-info-container': slotStatus(slotUjian) === 'upcoming',
                                }"
                            >
                                {{ slotStatusLabel(slotUjian) }}
                            </span>
                            <span
                                v-if="slotUjian.pendaftaran_status"
                                class="rounded-full px-3 py-1 text-label-md font-medium"
                                :class="registrationClass(slotUjian.pendaftaran_status)"
                            >
                                {{ registrationLabel(slotUjian.pendaftaran_status) }}
                            </span>
                            <BaseButton
                                v-if="canRegister(slotUjian)"
                                type="button"
                                size="sm"
                                @click="openRegistration(slotUjian)"
                            >
                                Daftar Ujian
                            </BaseButton>
                            <BaseButton
                                v-else-if="slotStatus(slotUjian) === 'active' && slotUjian.pendaftaran_status === 'approved'"
                                :href="route('ujian.pemeriksaan-awal', slotUjian.id)"
                                size="sm"
                            >
                                <IconClipboardCheck :size="16" /> Mulai Ujian
                            </BaseButton>
                            <BaseButton
                                v-if="canCancel(slotUjian)"
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="cancelRegistration(slotUjian)"
                            >
                                Batalkan
                            </BaseButton>
                        </div>
                    </div>
                </div>
                <div v-else class="py-4 text-center text-body-md text-text-muted">
                    Belum ada slot ujian tersedia untuk jadwal ini.
                </div>
            </BaseCard>
        </template>

        <div v-else class="rounded-2xl border border-outline-variant/30 bg-surface-white px-6 py-12 text-center shadow-soft">
            <IconCalendarEvent class="mx-auto mb-4 text-text-muted" :size="48" stroke="1.5" />
            <p class="text-body-md text-text-muted">Belum ada jadwal ujian yang tersedia.</p>
        </div>

        <BaseModal
            :show="showRegistrationModal"
            max-width="xl"
            aria-labelledby="registration-modal-title"
            aria-describedby="registration-modal-description"
            @close="closeRegistration"
        >
            <template #header>
                <BaseModalHeader>
                    <div class="px-6 py-4 sm:px-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-label-md font-semibold uppercase tracking-wide text-secondary">Pendaftaran Official</p>
                                 <h2 id="registration-modal-title" class="mt-1 text-headline-md font-bold text-text-heading">{{ selectedSlot?.tanggal ? formatDate(selectedSlot.tanggal) : 'Daftar Ujian' }}</h2>
                                 <p id="registration-modal-description" class="mt-1 text-body-md text-text-muted">Upload bukti pembayaran untuk diproses admin.</p>
                            </div>
                            <button type="button" class="text-text-muted hover:text-text-heading" aria-label="Tutup" @click="closeRegistration">&times;</button>
                        </div>
                    </div>
                </BaseModalHeader>
            </template>

            <template #default>
                <form id="registration-form" class="space-y-5 px-6 pb-6 pt-6 sm:px-8 sm:pb-8 sm:pt-8" @submit.prevent="submitRegistration">
                    <BaseFormGrid>
                        <div>
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">NIM</label>
                            <input v-model="registrationForm.nim" type="text" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading" />
                            <p v-if="registrationForm.errors.nim" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.nim }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">Tahun Angkatan</label>
                            <input v-model="registrationForm.tahun_angkatan" type="number" min="2000" max="2100" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading" />
                            <p v-if="registrationForm.errors.tahun_angkatan" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.tahun_angkatan }}</p>
                        </div>
                        <DropDown v-model="registrationForm.fakultas_id" :options="fakultas" label="Fakultas" option-label="nama" option-value="id" placeholder="Pilih fakultas" />
                        <DropDown v-model="registrationForm.program_studi_id" :options="programStudi" label="Program Studi" option-label="nama" option-value="id" placeholder="Pilih program studi" />
                    </BaseFormGrid>
                    <p v-if="registrationForm.errors.fakultas_id" class="text-xs text-error-red">{{ registrationForm.errors.fakultas_id }}</p>
                    <p v-if="registrationForm.errors.program_studi_id" class="text-xs text-error-red">{{ registrationForm.errors.program_studi_id }}</p>

                    <div>
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">Bukti Pembayaran</label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-outline-variant px-4 py-4 hover:border-secondary">
                            <IconUpload :size="20" class="text-text-muted" />
                            <span class="text-body-md text-text-body">{{ registrationForm.bukti_pembayaran?.name || 'Pilih file JPG atau PNG' }}</span>
                            <input type="file" accept=".jpg,.jpeg,.png" class="hidden" @change="selectPaymentProof" />
                        </label>
                        <p class="mt-1 text-label-md text-text-muted">Maksimal 5MB.</p>
                        <p v-if="registrationForm.errors.bukti_pembayaran" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.bukti_pembayaran }}</p>
                    </div>

                </form>
            </template>

            <template #footer>
                <BaseModalFooter>
                    <div class="px-6 pb-3 sm:px-8">
                        <BaseFormActions
                            form="registration-form"
                            :processing="registrationForm.processing"
                            @cancel="closeRegistration"
                        />
                    </div>
                </BaseModalFooter>
            </template>
        </BaseModal>
    </TataLetakDasbor>
</template>
