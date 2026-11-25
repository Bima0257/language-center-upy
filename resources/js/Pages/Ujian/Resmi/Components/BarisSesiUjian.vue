<script setup>
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import { statusPendaftaranLabel, statusPendaftaranVariant } from '@/constants/statusPendaftaran';
import {
    bisaDaftarSlot,
    bisaMulaiSlot,
    slotStatus,
    slotStatusLabel,
    slotStatusVariant,
} from '@/constants/statusSlotUjian';
import { IconCalendarEvent, IconClipboardCheck, IconClock, IconUsers } from '@tabler/icons-vue';

defineProps({
    slotUjian: { type: Object, required: true },
});

function formatHari(value) {
    return value
        ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
        : '-';
}

function formatTime(value) {
    return value ? value.substring(0, 5) : '-';
}

function sudahDisetujui(slotUjian) {
    return slotUjian.pendaftaran_status === 'approved';
}

function labelTombolMulai(slotUjian) {
    return slotStatus(slotUjian) === 'past' ? 'Sesi Selesai' : 'Belum Waktunya';
}
</script>

<template>
    <div class="px-5 py-4 transition-colors hover:bg-surface-container-low/40">
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-[minmax(0,1fr)_7.5rem_8.5rem] sm:items-center xl:grid-cols-[minmax(0,1fr)_7.5rem_8.5rem_20rem]">
            <p class="col-span-2 flex min-w-0 items-center gap-1.5 text-body-md font-medium leading-snug text-text-heading sm:col-span-1">
                <IconCalendarEvent :size="16" class="shrink-0 text-text-muted" />
                <span class="min-w-0">{{ formatHari(slotUjian.tanggal) }}</span>
            </p>

            <p class="flex min-w-0 items-center gap-1.5 whitespace-nowrap text-label-md leading-snug text-text-muted tabular-nums">
                <IconClock :size="14" class="shrink-0" />
                <span>{{ formatTime(slotUjian.waktu_mulai) }}&ndash;{{ formatTime(slotUjian.waktu_selesai) }}</span>
            </p>

            <p
                v-if="!sudahDisetujui(slotUjian)"
                class="flex min-w-0 items-center gap-1.5 whitespace-nowrap text-label-md leading-snug text-text-muted tabular-nums"
            >
                <IconUsers :size="14" class="shrink-0" />
                <span>Kuota {{ slotUjian.pendaftaran_disetujui_count || 0 }}/{{ slotUjian.kapasitas_peserta }}</span>
            </p>

            <div
                class="col-span-2 flex flex-wrap items-center justify-end gap-2 sm:col-span-3"
                :class="sudahDisetujui(slotUjian) ? 'xl:col-span-2 xl:col-start-3' : 'xl:col-span-1'"
            >
                <template v-if="!sudahDisetujui(slotUjian)">
                    <BaseBadge :variant="slotStatusVariant(slotUjian)">
                        {{ slotStatusLabel(slotUjian) }}
                    </BaseBadge>
                    <BaseBadge
                        v-if="slotUjian.pendaftaran_status"
                        :variant="statusPendaftaranVariant(slotUjian.pendaftaran_status)"
                    >
                        {{ statusPendaftaranLabel(slotUjian.pendaftaran_status) }}
                    </BaseBadge>
                </template>

                <BaseButton
                    v-if="sudahDisetujui(slotUjian) && bisaMulaiSlot(slotUjian)"
                    :href="route('ujian.pemeriksaan-awal', slotUjian.id)"
                    size="sm"
                >
                    <IconClipboardCheck :size="16" />
                    Mulai Ujian
                </BaseButton>
                <BaseButton
                    v-else-if="sudahDisetujui(slotUjian)"
                    type="button"
                    size="sm"
                    disabled
                >
                    <IconClock :size="16" />
                    {{ labelTombolMulai(slotUjian) }}
                </BaseButton>
                <BaseButton
                    v-else-if="bisaDaftarSlot(slotUjian)"
                    :href="route('ujian.pendaftaran.daftar')"
                    size="sm"
                >
                    Daftar Ujian
                </BaseButton>
            </div>
        </div>
    </div>
</template>


