<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import JenisTesTable from './components/JenisTesTable.vue';
import JenisTesFormModal from './components/JenisTesFormModal.vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    jenisTes: { type: Array, default: () => [] },
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
const creating = ref(false);
const editingType = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function openCreate() {
    creating.value = true;
    editingType.value = null;
    form.clearErrors();
    form.reset();
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.jenis-tes.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
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
    editForm.put(route('admin.data-induk.jenis-tes.perbarui', editingType.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingType.value = null;
    creating.value = false;
    editForm.reset();
}

async function destroy(type) {
    if (!await confirm.confirm(`Hapus jenis tes "${type.nama}"?`)) return;
    router.delete(route('admin.data-induk.jenis-tes.hapus', type.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Data Induk - Jenis Tes" />
    <TataLetakDasbor title="Data Induk Jenis Tes" subtitle="Kelola kategori jenis tes">
        <JenisTesTable :jenis-tes="jenisTes" @create="openCreate" @edit="startEdit" @delete="destroy" />
        <JenisTesFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
