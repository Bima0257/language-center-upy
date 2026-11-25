<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import PendaftaranUjianModal from '@/Components/Ujian/PendaftaranUjianModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { statusPendaftaranLabel, statusPendaftaranVariant } from '@/constants/statusPendaftaran';
import {
    IconBan,
    IconCalendarEvent,
    IconCircleCheck,
    IconClock,
    IconFileDescription,
    IconHistory,
    IconUsers,
    IconX,
} from '@tabler/icons-vue';

const props = defineProps({
    periodeUjian: { type: Array, default: () => [] },
    pendaftaranSaya: { type: Array, default: () => [] },
    profil: { type: Object, default: null },
    fakultas: { type: Array, default: () => [] },
    programStudi: { type: Array, default: () => [] },
    prodiTerkunci: { type: Boolean, default: false },
});

const confirm = useConfirm();
const showRegistrationModal = ref(false);
const selectedSlot = ref(null);

function formatDate(date) {
    return date
        ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
        : '-';
}

function formatTime(time) {
    if (!time) return '-';

    return time.substring(0, 5);
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

function slotStatusVariant(slotUjian) {
    return {
        active: 'success',
        past: 'neutral',
        upcoming: 'pastel-blue',
    }[slotStatus(slotUjian)] || 'neutral';
}

function isActiveRegistration(status) {
    return ['pending', 'approved'].includes(status);
}

function canRegister(slotUjian) {
    return slotUjian.pendaftaran_terbuka === true
        && slotStatus(slotUjian) === 'upcoming'
        && !isActiveRegistration(slotUjian.pendaftaran_status);
}

function canCancelPendaftaran(pendaftaran) {
    return isActiveRegistration(pendaftaran.status)
        && pendaftaran.slot_ujian !== null
        && slotStatus(pendaftaran.slot_ujian) === 'upcoming';
}

function statusIconFor(status) {
    return {
        pending: IconClock,
        approved: IconCircleCheck,
        rejected: IconX,
        cancelled: IconBan,
    }[status] || null;
}

function descriptionToneFor(status) {
    return {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'muted',
    }[status] || 'muted';
}

function openRegistration(slotUjian) {
    selectedSlot.value = slotUjian;
    showRegistrationModal.value = true;
}

function closeRegistration() {
    showRegistrationModal.value = false;
}

async function cancelPendaftaran(pendaftaran) {
    if (!await confirm.confirm('Batalkan pendaftaran pada slot ini?')) return;

    router.delete(route('ujian.pendaftaran.batalkan', pendaftaran.id), {
        preserveScroll: true,
    });
}

const slotTersedia = computed(() =>
    props.periodeUjian.flatMap((periode) =>
        (periode.slot_ujian || [])
            .filter(canRegister)
            .map((slotUjian) => ({ periode, slotUjian })),
    ),
);

const jumlahSlotTersedia = computed(() => slotTersedia.value.length);

const tabs = computed(() => [
    { value: 'dibuka', label: `Pendaftaran Dibuka (${jumlahSlotTersedia.value})` },
    { value: 'riwayat', label: `Riwayat Pendaftaran (${props.pendaftaranSaya.length})` },
]);

const activeTab = ref(
    jumlahSlotTersedia.value === 0 && props.pendaftaranSaya.length > 0 ? 'riwayat' : 'dibuka',
);
</script>

<template>
    <Head title="Pendaftaran Ujian Resmi" />
    <TataLetakDasbor
        title="Pendaftaran Ujian Resmi"
        subtitle="Informasi pendaftaran dan proses daftar ujian resmi"
    >
        <div class="relative">
            <BaseTabsChrome
                v-model="activeTab"
                :items="tabs"
                aria-label="Pendaftaran ujian"
                class="relative z-10 -mb-px px-5"
            />

            <BaseCard padding="p-0" class="border-t-0! overflow-hidden">
                <div v-if="activeTab === 'dibuka'" class="p-4">
                    <div v-if="slotTersedia.length > 0" class="space-y-3">
                        <BaseCompactCard
                            v-for="{ periode, slotUjian } in slotTersedia"
                            :key="`slot-${slotUjian.id}`"
                            :icon="IconCalendarEvent"
                            :title="periode.ujian?.judul || '-'"
                            :subtitle="`${periode.judul} · ${periode.ujian?.program_tes?.nama || '-'}`"
                            :status-label="slotStatusLabel(slotUjian)"
                            :status-variant="slotStatusVariant(slotUjian)"
                            :meta-items="[
                                { icon: IconCalendarEvent, label: `${formatDate(slotUjian.tanggal)} · ${formatTime(slotUjian.waktu_mulai)}–${formatTime(slotUjian.waktu_selesai)}` },
                                { icon: IconUsers, label: `Kuota ${slotUjian.pendaftaran_disetujui_count || 0}/${slotUjian.kapasitas_peserta}` },
                            ]"
                            :elevated="false"
                        >
                            <template #actions>
                                <BaseButton size="sm" class="w-full sm:w-auto" @click="openRegistration(slotUjian)">
                                    Daftar Ujian
                                </BaseButton>
                            </template>
                        </BaseCompactCard>
                    </div>

                    <div v-else class="py-8 text-center">
                        <IconCalendarEvent class="mx-auto mb-3 text-text-muted" :size="32" stroke="1.5" />
                        <p class="text-body-md text-text-muted">Belum ada pendaftaran ujian resmi yang dibuka saat ini.</p>
                    </div>
                </div>

                <div v-else class="p-4">
                    <div v-if="props.pendaftaranSaya.length > 0" class="space-y-3">
                        <BaseCompactCard
                            v-for="pendaftaran in props.pendaftaranSaya"
                            :key="`riwayat-${pendaftaran.id}`"
                            :icon="IconFileDescription"
                            :title="pendaftaran.slot_ujian?.periode_ujian?.ujian?.judul || '-'"
                            :subtitle="`${pendaftaran.slot_ujian?.periode_ujian?.judul || '-'} · ${pendaftaran.slot_ujian?.periode_ujian?.ujian?.program_tes?.nama || '-'}`"
                            :status-label="statusPendaftaranLabel(pendaftaran.status)"
                            :status-variant="statusPendaftaranVariant(pendaftaran.status)"
                            :status-icon="statusIconFor(pendaftaran.status)"
                            :meta-items="[
                                { icon: IconCalendarEvent, label: `${formatDate(pendaftaran.slot_ujian?.tanggal)} · ${formatTime(pendaftaran.slot_ujian?.waktu_mulai)}–${formatTime(pendaftaran.slot_ujian?.waktu_selesai)}` },
                            ]"
                            :description="pendaftaran.catatan_admin || ''"
                            :description-tone="descriptionToneFor(pendaftaran.status)"
                            :elevated="false"
                        >
                            <template v-if="canCancelPendaftaran(pendaftaran)" #actions>
                                <BaseButton
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    class="w-full sm:w-auto"
                                    @click="cancelPendaftaran(pendaftaran)"
                                >
                                    Batalkan
                                </BaseButton>
                            </template>
                        </BaseCompactCard>
                    </div>

                    <div v-else class="py-8 text-center">
                        <IconHistory class="mx-auto mb-3 text-text-muted" :size="32" stroke="1.5" />
                        <p class="text-body-md text-text-muted">Belum ada riwayat pendaftaran.</p>
                    </div>
                </div>
            </BaseCard>
        </div>

        <PendaftaranUjianModal
            :show="showRegistrationModal"
            :slot-ujian="selectedSlot"
            :profil="props.profil"
            :fakultas="props.fakultas"
            :program-studi="props.programStudi"
            :prodi-terkunci="props.prodiTerkunci"
            @close="closeRegistration"
            @saved="closeRegistration"
        />
    </TataLetakDasbor>
</template>
