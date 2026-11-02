<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { IconCalendarEvent, IconClock, IconEdit, IconExternalLink, IconUsers } from '@tabler/icons-vue';

const props = defineProps({
    periodePendaftaranUjian: { type: Object, required: true },
    slotUjian: { type: Array, default: () => [] },
});

function formatDateTime(value) {
    return value
        ? new Date(value).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        })
        : '-';
}

function formatDate(value) {
    return value
        ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
        : '-';
}

function formatTime(value) {
    return value ? String(value).substring(0, 5) : '-';
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

function pesertaUrl(slotId) {
    const baseUrl = route('admin.pendaftaran-ujian.peserta', props.periodePendaftaranUjian.id);
    return `${baseUrl}?slot_ujian_id=${encodeURIComponent(slotId)}`;
}
</script>

<template>
    <Head :title="periodePendaftaranUjian.nama" />
    <TataLetakDasbor
        :title="periodePendaftaranUjian.nama"
        subtitle="Kelola window pendaftaran dan slot peserta"
        :back-href="route('admin.pendaftaran-ujian.daftar')"
        back-label="Periode Pendaftaran"
    >
        <div class="space-y-6">
            <BaseCard>
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
                    <div>
                        <p class="text-label-md font-semibold uppercase tracking-wide text-secondary">{{ periodePendaftaranUjian.periode_ujian?.ujian?.jenis_tes?.nama || 'Ujian' }}</p>
                        <h2 class="mt-1 text-headline-md font-bold text-text-heading">{{ periodePendaftaranUjian.periode_ujian?.ujian?.judul || '-' }}</h2>
                        <p class="mt-1 text-body-md text-text-muted">{{ periodePendaftaranUjian.periode_ujian?.judul || '-' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-label-md font-medium" :class="statusClass(periodePendaftaranUjian.status_pendaftaran)">
                            {{ statusLabel(periodePendaftaranUjian.status_pendaftaran) }}
                        </span>
                        <BaseButton :href="route('admin.pendaftaran-ujian.ubah', periodePendaftaranUjian.id)" variant="secondary" size="sm">
                            <IconEdit :size="16" /> Ubah Periode
                        </BaseButton>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-surface-container-low px-4 py-3">
                        <p class="text-label-md text-text-muted">Dibuka pada</p>
                        <p class="mt-1 text-body-md font-semibold text-text-heading">{{ formatDateTime(periodePendaftaranUjian.dibuka_pada) }}</p>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low px-4 py-3">
                        <p class="text-label-md text-text-muted">Ditutup pada</p>
                        <p class="mt-1 text-body-md font-semibold text-text-heading">{{ formatDateTime(periodePendaftaranUjian.ditutup_pada) }}</p>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low px-4 py-3">
                        <p class="text-label-md text-text-muted">Pelaksanaan ujian</p>
                        <p class="mt-1 text-body-md font-semibold text-text-heading">
                            {{ formatDate(periodePendaftaranUjian.periode_ujian?.tanggal_mulai) }} - {{ formatDate(periodePendaftaranUjian.periode_ujian?.tanggal_selesai) }}
                        </p>
                    </div>
                </div>

                <p v-if="periodePendaftaranUjian.catatan" class="mt-5 whitespace-pre-line text-body-md text-text-body">{{ periodePendaftaranUjian.catatan }}</p>
            </BaseCard>

            <BaseCard :padding="false" class="overflow-hidden">
                <div class="flex flex-col justify-between gap-3 border-b border-outline-variant/30 bg-surface-container-low px-5 py-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-title-lg font-semibold text-text-heading">Slot Peserta</h2>
                        <p class="mt-1 text-body-md text-text-muted">{{ slotUjian.length }} slot terhubung pada periode ujian ini.</p>
                    </div>
                    <BaseButton :href="route('admin.periode-ujian.detail', periodePendaftaranUjian.periode_ujian_id)" variant="secondary" size="sm">
                        <IconExternalLink :size="16" /> Kelola Slot
                    </BaseButton>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Slot</th>
                                <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Waktu</th>
                                <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Kapasitas</th>
                                <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Peserta</th>
                                <th class="px-5 py-4 text-label-md font-semibold text-text-muted">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="slot in slotUjian" :key="slot.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-surface-container-low">
                                            <IconCalendarEvent :size="18" class="text-secondary" />
                                        </div>
                                        <span class="font-medium text-text-heading">{{ formatDate(slot.tanggal) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-body-md text-text-body">
                                    <span class="inline-flex items-center gap-1.5"><IconClock :size="15" /> {{ formatTime(slot.waktu_mulai) }} - {{ formatTime(slot.waktu_selesai) }}</span>
                                </td>
                                <td class="px-5 py-4 text-body-md text-text-body">{{ slot.kapasitas_peserta }}</td>
                                <td class="px-5 py-4 text-body-md text-text-body">
                                    <span class="inline-flex items-center gap-1.5"><IconUsers :size="15" /> {{ slot.pendaftaran_ujian_count || 0 }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <BaseButton :href="pesertaUrl(slot.id)" variant="secondary" size="sm">Lihat Peserta</BaseButton>
                                </td>
                            </tr>
                            <tr v-if="slotUjian.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-body-md text-text-muted">Belum ada slot peserta. Kelola slot melalui penjadwalan ujian.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
