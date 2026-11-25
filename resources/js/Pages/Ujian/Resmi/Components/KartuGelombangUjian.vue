<script setup>
import { computed } from 'vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import BarisSesiUjian from '@/Pages/Ujian/Resmi/Components/BarisSesiUjian.vue';
import { slotStatus } from '@/constants/statusSlotUjian';
import { IconCalendarEvent } from '@tabler/icons-vue';

const props = defineProps({
    periode: { type: Object, required: true },
});

const semuaSesiSelesai = computed(() => {
    const daftarSlot = props.periode.slot_ujian || [];

    return daftarSlot.length > 0
        && daftarSlot.every(slotUjian => slotStatus(slotUjian) === 'past');
});

function formatDate(value) {
    return value
        ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
        : '-';
}

function periodeLabel(status) {
    return {
        upcoming: 'Pendaftaran akan dibuka',
        open: 'Pendaftaran dibuka',
        closed: 'Pendaftaran ditutup',
        inactive: 'Pendaftaran nonaktif',
    }[status] || 'Pendaftaran belum tersedia';
}

function periodeVariant(status) {
    return {
        upcoming: 'pastel-blue',
        open: 'success',
        closed: 'warning',
        inactive: 'neutral',
    }[status] || 'neutral';
}
</script>

<template>
    <BaseCard padding="p-0" class="overflow-hidden">
        <div class="flex flex-col gap-3 bg-surface-container-low px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface-white text-secondary">
                    <IconCalendarEvent :size="20" stroke="1.8" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-title-lg font-semibold text-text-heading">{{ periode.judul }}</h3>
                    <p class="mt-0.5 text-label-md leading-snug text-text-muted">
                        {{ formatDate(periode.tanggal_mulai) }} &ndash; {{ formatDate(periode.tanggal_selesai) }}
                    </p>
                </div>
            </div>

            <div v-if="!semuaSesiSelesai" class="shrink-0 self-start sm:self-center">
                <BaseBadge :variant="periodeVariant(periode.status_pendaftaran)">
                    {{ periodeLabel(periode.status_pendaftaran) }}
                </BaseBadge>
            </div>
        </div>

        <ul class="divide-y divide-outline-variant/30">
            <li v-for="slotUjian in periode.slot_ujian" :key="slotUjian.id">
                <BarisSesiUjian :slot-ujian="slotUjian" />
            </li>
        </ul>
    </BaseCard>
</template>
