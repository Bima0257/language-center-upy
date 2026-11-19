<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import AturanPenilaianTable from '../Components/AturanPenilaianTable.vue';
import AturanPenilaianFormModal from '../Components/AturanPenilaianFormModal.vue';

const props = defineProps({
    programTes: { type: Object, required: true },
    skema: { type: Object, required: true },
    aturanPenilaian: { type: Array, default: () => [] },
    interpretasiSkor: { type: Array, default: () => [] },
    total: { type: Object, default: () => ({ minimum: 0, maksimum: 0 }) },
});

const showModal = ref(false);
const editingRule = ref(null);
const form = useForm({
    tabel_konversi: {},
    skor_mentah_maksimum: 50,
    skor_skala_minimum: 100,
    skor_skala_maksimum: 200,
});

function bukaInterpretasi() {
    router.get(route('admin.data-induk.interpretasi-skor.program-tes.daftar', props.programTes.id));
}

function startEdit(rule) {
    editingRule.value = rule;
    form.clearErrors();
    form.tabel_konversi = { ...(rule.tabel_konversi || {}) };
    form.skor_mentah_maksimum = rule.skor_mentah_maksimum || 50;
    form.skor_skala_minimum = rule.skor_skala_minimum ?? 100;
    form.skor_skala_maksimum = rule.skor_skala_maksimum || 200;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingRule.value = null;
    form.reset();
    form.clearErrors();
}

function saveRule() {
    if (!editingRule.value) return;

    form.put(route(
        'admin.data-induk.aturan-penilaian.skema.section.perbarui',
        [props.skema.id, editingRule.value.section_id],
    ), {
        preserveScroll: true,
        onSuccess: closeModal,
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

            <p class="mt-2 text-label-md text-text-muted">
                Raw = jumlah jawaban benar pada section; skor skala = nilai hasil konversi.
            </p>
        </BaseCard>

        <AturanPenilaianTable
            :aturan-penilaian="props.aturanPenilaian"
            @edit="startEdit"
        />

        <AturanPenilaianFormModal
            :show="showModal"
            :form="form"
            :rule="editingRule"
            @close="closeModal"
            @submit="saveRule"
        />
    </TataLetakDasbor>
</template>
