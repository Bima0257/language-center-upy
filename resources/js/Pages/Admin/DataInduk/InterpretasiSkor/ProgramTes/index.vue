<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import InterpretasiSkorTable from '../Components/InterpretasiSkorTable.vue';
import InterpretasiSkorFormModal from '../Components/InterpretasiSkorFormModal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    programTes: { type: Object, required: true },
    skema: { type: Object, required: true },
    interpretasiSkor: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const showModal = ref(false);
const editingInterpretasi = ref(null);
const editForm = useForm({
    skema_penilaian_id: props.skema.id,
    program_tes_id: props.programTes.id,
    skor_minimum: '',
    skor_maksimum: '',
    tingkat_cefr: '',
    label_tingkat: '',
    lulus: true,
    deskripsi: '',
});

function goToBulkCreate() {
    router.visit(route('admin.data-induk.interpretasi-skor.program-tes.tambah-massal', {
        programTes: props.programTes.id,
    }));
}

function startEdit(interp) {
    editingInterpretasi.value = interp;
    editForm.reset();
    editForm.clearErrors();
    editForm.skema_penilaian_id = props.skema.id;
    editForm.program_tes_id = props.programTes.id;
    editForm.skor_minimum = interp.skor_minimum;
    editForm.skor_maksimum = interp.skor_maksimum;
    editForm.tingkat_cefr = interp.tingkat_cefr;
    editForm.label_tingkat = interp.label_tingkat;
    editForm.lulus = !!interp.lulus;
    editForm.deskripsi = interp.deskripsi || '';
    showModal.value = true;
}

function saveEdit() {
    if (!editingInterpretasi.value) return;

    editForm.put(route('admin.data-induk.interpretasi-skor.perbarui', editingInterpretasi.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingInterpretasi.value = null;
    editForm.reset();
    editForm.clearErrors();
    editForm.skema_penilaian_id = props.skema.id;
    editForm.program_tes_id = props.programTes.id;
}

async function destroy(interp) {
    if (!await confirm.confirm(`Hapus interpretasi ${interp.tingkat_cefr} (${interp.skor_minimum}-${interp.skor_maksimum})?`)) return;
    router.delete(route('admin.data-induk.interpretasi-skor.hapus', interp.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Interpretasi Skor - ${props.programTes.nama}`" />
    <TataLetakDasbor
        :title="`Interpretasi Skor - ${props.programTes.nama}`"
        :subtitle="`Kelola rentang interpretasi skor pada ${props.programTes.nama}`"
        :back-href="route('admin.data-induk.interpretasi-skor.daftar')"
    >
        <BaseCard class="mb-6">
            <p class="text-label-md text-text-muted">Skema Penilaian</p>
            <p class="mt-1 text-title-lg font-semibold text-text-heading">{{ props.programTes.nama }}</p>
            <p class="mt-3 text-label-md text-text-muted">
                {{ props.interpretasiSkor.length }} rentang interpretasi. Perubahan berlaku untuk sesi ujian baru; hasil ujian lama tetap memakai snapshot.
            </p>
        </BaseCard>

        <InterpretasiSkorTable
            :interpretasi-skor="props.interpretasiSkor"
            :program-tes="[props.programTes]"
            @create="goToBulkCreate"
            @edit="startEdit"
            @delete="destroy"
        />

        <InterpretasiSkorFormModal
            :show="showModal"
            editing
            :form="editForm"
            :program-tes="props.programTes"
            @close="closeModal"
            @submit="saveEdit"
        />
    </TataLetakDasbor>
</template>
