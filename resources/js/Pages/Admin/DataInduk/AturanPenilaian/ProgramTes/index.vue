<script setup>
import { Head, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import AturanPenilaianTable from '../Components/AturanPenilaianTable.vue';

const props = defineProps({
    programTes: { type: Object, required: true },
    skema: { type: Object, required: true },
    aturanPenilaian: { type: Array, default: () => [] },
    interpretasiSkor: { type: Array, default: () => [] },
    total: { type: Object, default: () => ({ minimum: 0, maksimum: 0 }) },
    memilikiUjianAktif: { type: Boolean, default: false },
});

function bukaInterpretasi() {
    router.get(route('admin.data-induk.interpretasi-skor.program-tes.daftar', props.programTes.id));
}

function ubahUrl(rule) {
    return route('admin.data-induk.aturan-penilaian.skema.section.ubah', {
        skema: props.skema.id,
        section: rule.section_id,
    });
}
</script>

<template>
    <Head :title="`Aturan Penilaian - ${props.programTes.nama}`" />
    <TataLetakDasbor
        :title="`Aturan Penilaian - ${props.programTes.nama}`"
        :subtitle="`Kelola skema penilaian pada ${props.programTes.nama}`"
        :back-href="route('admin.data-induk.aturan-penilaian.daftar')"
    >
        <BaseCard class="mb-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-label-md text-text-muted">Skema Penilaian</p>
                    <p class="mt-1 text-title-lg font-semibold text-text-heading">{{ props.programTes.nama }}</p>
                </div>

                <BaseButton variant="secondary" size="sm" @click="bukaInterpretasi">
                    Interpretasi Skor
                </BaseButton>
            </div>

            <div class="mt-4 flex flex-wrap gap-4 text-label-md text-text-muted">
                <span>Total skala: {{ props.total.minimum }}–{{ props.total.maksimum }}</span>
                <span>Interpretasi: {{ props.interpretasiSkor.length }} rentang</span>
            </div>

            <p class="mt-3 text-label-md text-text-muted">
                Perubahan aturan berlaku untuk sesi ujian baru; hasil ujian lama tetap memakai snapshot.
            </p>

            <p v-if="props.memilikiUjianAktif" class="mt-2 text-label-md text-text-muted">
                Program tes dipakai ujian aktif — aturan penilaian dikunci.
            </p>

            <p class="mt-2 text-label-md text-text-muted">
                Raw = jumlah jawaban benar pada section; skor skala = nilai hasil konversi.
            </p>
        </BaseCard>

        <AturanPenilaianTable
            :aturan-penilaian="props.aturanPenilaian"
            :ubah-url="ubahUrl"
            :locked="props.memilikiUjianAktif"
        />
    </TataLetakDasbor>
</template>
