<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FakultasFormModal from './Components/FakultasFormModal.vue';
import FakultasTable from './Components/FakultasTable.vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    fakultas: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    nama: '',
    kode: '',
    aktif: true,
});

const editForm = useForm({
    nama: '',
    kode: '',
    aktif: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingFaculty = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function openCreate() {
    creating.value = true;
    editingFaculty.value = null;
    form.clearErrors();
    form.reset();
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.fakultas.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

function startEdit(fakultas) {
    creating.value = false;
    editingFaculty.value = fakultas;
    editForm.clearErrors();
    editForm.reset();
    editForm.nama = fakultas.nama;
    editForm.kode = fakultas.kode;
    editForm.aktif = !!fakultas.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.data-induk.fakultas.perbarui', editingFaculty.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingFaculty.value = null;
    creating.value = false;
    editForm.reset();
}

async function destroy(fakultas) {
    if (!await confirm.confirm(`Hapus fakultas "${fakultas.nama}"?`)) return;
    router.delete(route('admin.data-induk.fakultas.hapus', fakultas.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Data Induk - Fakultas" />
    <TataLetakDasbor title="Data Induk Fakultas" subtitle="Kelola data fakultas">
        <FakultasTable :fakultas="fakultas" @create="openCreate" @edit="startEdit" @delete="destroy" />
        <FakultasFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
