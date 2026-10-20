<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramStudiFormModal from './Components/ProgramStudiFormModal.vue';
import ProgramStudiTable from './Components/ProgramStudiTable.vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    fakultas: { type: Object, required: true },
    programStudi: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const showModal = ref(false);
const editingProgramStudi = ref(null);

const editForm = useForm({
    fakultas_id: props.fakultas.id,
    nama: '',
    kode: '',
    aktif: true,
});

function goToBulkCreate() {
    router.visit(route('admin.data-induk.program-studi.fakultas.tambah-massal', props.fakultas.id));
}

function startEdit(programStudi) {
    editingProgramStudi.value = programStudi;
    editForm.clearErrors();
    editForm.reset();
    editForm.fakultas_id = props.fakultas.id;
    editForm.nama = programStudi.nama;
    editForm.kode = programStudi.kode;
    editForm.aktif = !!programStudi.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.data-induk.program-studi.perbarui', editingProgramStudi.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingProgramStudi.value = null;
    editForm.reset();
    editForm.fakultas_id = props.fakultas.id;
}

async function destroy(programStudi) {
    if (!await confirm.confirm(`Hapus prodi "${programStudi.nama}"?`)) return;
    router.delete(route('admin.data-induk.program-studi.hapus', programStudi.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Program Studi - ${props.fakultas.nama}`" />
    <TataLetakDasbor
        :title="`Program Studi - ${props.fakultas.nama}`"
        :subtitle="`Kelola program studi pada ${props.fakultas.nama}`"
    >
        <ProgramStudiTable
            :program-studi="props.programStudi"
            :back-url="route('admin.data-induk.program-studi.daftar')"
            @create="goToBulkCreate"
            @edit="startEdit"
            @delete="destroy"
        />

        <ProgramStudiFormModal
            :show="showModal"
            :form="editForm"
            :fakultas="[props.fakultas]"
            lock-faculty
            @close="closeModal"
            @submit="saveEdit"
        />
    </TataLetakDasbor>
</template>
