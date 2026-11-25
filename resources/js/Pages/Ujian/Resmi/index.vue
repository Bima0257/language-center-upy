<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import InfoUjianResmi from '@/Pages/Ujian/Resmi/Components/InfoUjianResmi.vue';
import KartuGelombangUjian from '@/Pages/Ujian/Resmi/Components/KartuGelombangUjian.vue';
import { slotRelevanUntukPeserta } from '@/constants/statusSlotUjian';
import { IconCalendarEvent } from '@tabler/icons-vue';

const props = defineProps({
    periodeUjian: { type: Array, default: () => [] },
});

const daftarUjian = computed(() => {
    const grup = new Map();

    for (const periode of props.periodeUjian) {
        const slotRelevan = (periode.slot_ujian || []).filter(slotRelevanUntukPeserta);

        if (slotRelevan.length === 0 || !periode.ujian) {
            continue;
        }

        if (!grup.has(periode.ujian.id)) {
            grup.set(periode.ujian.id, { ujian: periode.ujian, periode: [] });
        }

        grup.get(periode.ujian.id).periode.push({ ...periode, slot_ujian: slotRelevan });
    }

    return [...grup.values()];
});
</script>

<template>
    <Head title="Ujian Resmi" />
    <TataLetakDasbor title="Ujian Resmi" subtitle="Daftar ujian resmi yang tersedia untuk Anda">
        <EmptyState
            v-if="daftarUjian.length === 0"
            :icon="IconCalendarEvent"
            title="Belum Ada Ujian Resmi"
            description="Saat ini belum ada ujian resmi yang berjadwal untuk Anda."
        />

        <div v-else class="space-y-6">
            <section v-for="grup in daftarUjian" :key="grup.ujian.id" class="space-y-5">
                <InfoUjianResmi :ujian="grup.ujian" />

                <KartuGelombangUjian
                    v-for="periode in grup.periode"
                    :key="periode.id"
                    :periode="periode"
                />
            </section>
        </div>
    </TataLetakDasbor>
</template>
