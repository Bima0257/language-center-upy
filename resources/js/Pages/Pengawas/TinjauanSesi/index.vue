<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import InformasiPeserta from '@/Pages/Pengawas/TinjauanSesi/components/InformasiPeserta.vue';
import RingkasanSkor from '@/Pages/Pengawas/TinjauanSesi/components/RingkasanSkor.vue';
import FormPenghentianSesi from '@/Pages/Pengawas/TinjauanSesi/components/FormPenghentianSesi.vue';
import PanelKeputusanTinjauan from '@/Pages/Pengawas/TinjauanSesi/components/PanelKeputusanTinjauan.vue';
import LinimasaPelanggaran from '@/Pages/Pengawas/TinjauanSesi/components/LinimasaPelanggaran.vue';
import { useConfirm } from '@/Composables/useConfirm';

const confirm = useConfirm();

const props = defineProps({
    sesiUjian: { type: Object, required: true },
});

const reviewForm = useForm({
    status_tinjauan: '',
    catatan_tinjauan: '',
});

function submitReview() {
    reviewForm.post(route('pengawas.sesi.tinjauan', props.sesiUjian.id));
}

const terminateForm = useForm({
    alasan: 'Dihentikan oleh pengawas.',
});

async function terminateSession() {
    if (!await confirm.confirm('Yakin ingin menghentikan sesi ujian ini?')) return;
    terminateForm.post(route('pengawas.sesi.hentikan', props.sesiUjian.id));
}

function refresh() {
    router.reload({ preserveScroll: true, only: ['sesiUjian'] });
}

</script>

<template>
    <Head title="Tinjauan Sesi" />
    <TataLetakDasbor title="Tinjauan Sesi" subtitle="Tinjau pelanggaran dan putuskan status sesi">
        <div class="flex justify-end mb-4">
            <button @click="refresh"
                    class="bg-surface-white border border-outline-variant text-primary px-5 py-2 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all active:scale-95">
                Refresh → Segarkan
            </button>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <InformasiPeserta :sesi-ujian="sesiUjian" />

                <RingkasanSkor v-if="sesiUjian.skor_total !== null" :sesi-ujian="sesiUjian" />

                <LinimasaPelanggaran :pelanggaran="sesiUjian.catatan_pelanggaran" />

                <FormPenghentianSesi
                    v-if="sesiUjian.status === 'in_progress' || sesiUjian.status === 'terminated'"
                    :form="terminateForm"
                    @submit="terminateSession"
                />
            </div>

            <PanelKeputusanTinjauan
                :sesi-ujian="sesiUjian"
                :form="reviewForm"
                @submit="submitReview"
            />
        </div>
    </TataLetakDasbor>
</template>
