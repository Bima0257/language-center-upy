<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { IconCalendarEvent, IconClock, IconClipboardCheck, IconUsers } from '@tabler/icons-vue';

defineProps({
    periodeUjian: { type: Array, default: () => [] },
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatTime(time) {
    if (!time) return '-';
    return time.substring(0, 5);
}

function slotStatus(slotUjian) {
    const now = new Date();
    const slotDate = new Date(slotUjian.tanggal);
    const [h, m] = (slotUjian.waktu_mulai || '00:00').split(':').map(Number);
    slotDate.setHours(h, m, 0, 0);

    const endSlot = new Date(slotDate);
    const [eh, em] = (slotUjian.waktu_selesai || '23:59').split(':').map(Number);
    endSlot.setHours(eh, em, 0, 0);

    if (now >= slotDate && now <= endSlot) return 'active';
    if (now > endSlot) return 'past';
    return 'upcoming';
}

function slotStatusLabel(slotUjian) {
    const s = slotStatus(slotUjian);
    return { active: 'Berlangsung', past: 'Selesai', upcoming: 'Akan Datang' }[s];
}
</script>

<template>
    <Head title="Ujian" />
    <TataLetakDasbor title="Ujian" subtitle="Jadwal ujian resmi Anda">
        <div v-if="periodeUjian.length === 0" class="text-center py-16">
            <IconCalendarEvent class="mx-auto text-text-muted mb-4" :size="48" stroke="1.5" />
            <p class="text-text-muted text-body-md">Belum ada jadwal ujian yang tersedia.</p>
        </div>

        <div class="space-y-6">
            <BaseCard v-for="periodeUjian in periodeUjian" :key="periodeUjian.id">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-title-lg font-bold text-text-heading">{{ periodeUjian.judul }}</h2>
                         <p class="text-text-muted text-body-md mt-1">{{ periodeUjian.ujian?.judul || '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2 text-label-md text-text-muted">
                        <IconCalendarEvent :size="16" />
                        <span>{{ formatDate(periodeUjian.tanggal_mulai) }} — {{ formatDate(periodeUjian.tanggal_selesai) }}</span>
                    </div>
                </div>

                 <div v-if="periodeUjian.slot_ujian?.length" class="divide-y divide-outline-variant/20">
                    <div
                         v-for="slotUjian in periodeUjian.slot_ujian"
                        :key="slotUjian.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-surface-container-low flex items-center justify-center shrink-0">
                                <IconClock class="text-secondary" :size="20" />
                            </div>
                            <div>
                                <p class="font-medium text-text-heading">
                                     {{ new Date(slotUjian.tanggal).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </p>
                                <p class="text-label-md text-text-muted">
                                    {{ formatTime(slotUjian.waktu_mulai) }} — {{ formatTime(slotUjian.waktu_selesai) }}
                                    · Toleransi keterlambatan {{ slotUjian.toleransi_keterlambatan_menit }} menit
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 text-label-md text-text-muted">
                                <IconUsers :size="14" />
                                <span>Maks. {{ slotUjian.kapasitas_peserta }} peserta</span>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-label-md font-medium"
                                :class="{
                                    'bg-success-container text-on-success-container': slotStatus(slotUjian) === 'active',
                                    'bg-neutral-container text-on-neutral-container': slotStatus(slotUjian) === 'past',
                                    'bg-info-container text-on-info-container': slotStatus(slotUjian) === 'upcoming',
                                }"
                            >
                                {{ slotStatusLabel(slotUjian) }}
                            </span>
                            <BaseButton
                                v-if="slotStatus(slotUjian) === 'active'"
                                :href="route('ujian.pemeriksaan-awal', slotUjian.id)"
                                size="sm"
                            >
                                <IconClipboardCheck :size="16" /> Ikuti Ujian
                            </BaseButton>
                        </div>
                    </div>
                </div>
                <div v-else class="py-4 text-center text-text-muted text-body-md">
                    Belum ada slotUjian tersedia untuk jadwal ini.
                </div>
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
