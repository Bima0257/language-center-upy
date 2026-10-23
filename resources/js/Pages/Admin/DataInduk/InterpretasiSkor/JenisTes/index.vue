<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import InterpretasiSkorTable from '../components/InterpretasiSkorTable.vue';
import InterpretasiSkorFormModal from '../components/InterpretasiSkorFormModal.vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    jenisTes: { type: Object, required: true },
    interpretasiSkor: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const showModal = ref(false);
const editingInterpretasi = ref(null);
const editForm = useForm({
    jenis_tes_id: props.jenisTes.id,
    skor_minimum: '',
    skor_maksimum: '',
    tingkat_cefr: '',
    label_tingkat: '',
    lulus: true,
    deskripsi: '',
});

function goToBulkCreate() {
    router.visit(route('admin.data-induk.interpretasi-skor.jenis-tes.tambah-massal', props.jenisTes.id));
}

function startEdit(interp) {
    editingInterpretasi.value = interp;
    editForm.reset();
    editForm.clearErrors();
    editForm.jenis_tes_id = props.jenisTes.id;
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
    editForm.jenis_tes_id = props.jenisTes.id;
}

async function destroy(interp) {
    if (!await confirm.confirm(`Hapus interpretasi ${interp.tingkat_cefr} (${interp.skor_minimum}-${interp.skor_maksimum})?`)) return;
    router.delete(route('admin.data-induk.interpretasi-skor.hapus', interp.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Interpretasi Skor - ${props.jenisTes.nama}`" />
    <TataLetakDasbor
        :title="`Interpretasi Skor - ${props.jenisTes.nama}`"
        :subtitle="`Kelola rentang interpretasi skor pada ${props.jenisTes.nama}`"
    >
        <InterpretasiSkorTable
            :interpretasi-skor="props.interpretasiSkor"
            :jenis-tes="[props.jenisTes]"
            :back-url="route('admin.data-induk.interpretasi-skor.daftar')"
            @create="goToBulkCreate"
            @edit="startEdit"
            @delete="destroy"
        />

        <InterpretasiSkorFormModal
            :show="showModal"
            editing
            :form="editForm"
            :jenis-tes="props.jenisTes"
            submit-label="Simpan Perubahan"
            @close="closeModal"
            @submit="saveEdit"
        />
    </TataLetakDasbor>
</template>
