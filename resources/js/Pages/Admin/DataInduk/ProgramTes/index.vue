<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramTesTable from './Components/ProgramTesTable.vue';
import ProgramTesFormModal from './Components/ProgramTesFormModal.vue';
import DetailProgramTesModal from './Components/DetailProgramTesModal.vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    programTes: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    nama: '',
    batas_pelanggaran: 3,
    deskripsi: '',
    aktif: true,
});

const editForm = useForm({
    kode: '',
    nama: '',
    batas_pelanggaran: 3,
    deskripsi: '',
    aktif: true,
});

const showModal = ref(false);
const showDetailModal = ref(false);
const creating = ref(false);
const editingType = ref(null);
const detailType = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function openCreate() {
    creating.value = true;
    editingType.value = null;
    form.clearErrors();
    form.reset();
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.program-tes.simpan'), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function openDetail(type) {
    detailType.value = type;
    showDetailModal.value = true;
}

function closeDetail() {
    showDetailModal.value = false;
}

function startEdit(type) {
    creating.value = false;
    editingType.value = type;
    editForm.clearErrors();
    editForm.reset();
    editForm.kode = type.kode || '';
    editForm.nama = type.nama;
    editForm.batas_pelanggaran = type.batas_pelanggaran ?? 3;
    editForm.deskripsi = type.deskripsi || '';
    editForm.aktif = !!type.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.data-induk.program-tes.perbarui', editingType.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
}

async function destroy(type) {
    if (!await confirm.confirm(`Hapus program tes "${type.nama}"?`)) return;
    router.delete(route('admin.data-induk.program-tes.hapus', type.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Data Induk - Program Tes" />
    <TataLetakDasbor title="Data Induk Program Tes" subtitle="Kelola data program tes">
        <ProgramTesTable :program-tes="programTes" @create="openCreate" @edit="startEdit" @delete="destroy" @detail="openDetail" />
        <ProgramTesFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
        <DetailProgramTesModal
            :show="showDetailModal"
            :program-tes="detailType"
            @close="closeDetail"
        />
    </TataLetakDasbor>
</template>
