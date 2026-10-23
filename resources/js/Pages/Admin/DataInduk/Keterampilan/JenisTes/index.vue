<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import KeterampilanFormModal from '../components/KeterampilanFormModal.vue';
import KeterampilanTable from '../components/KeterampilanTable.vue';
import { computed, ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    jenisTes: { type: Object, required: true },
    keterampilan: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const form = useForm({
    jenis_tes_id: props.jenisTes.id,
    kode: '',
    nama: '',
    deskripsi: '',
    aktif: true,
});

const editForm = useForm({
    jenis_tes_id: props.jenisTes.id,
    kode: '',
    nama: '',
    deskripsi: '',
    aktif: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingSkill = ref(null);
const reordering = ref(false);
const originalOrder = ref('');

const modalForm = computed(() => creating.value ? form : editForm);

watch(() => props.keterampilan, (list) => {
    originalOrder.value = list.map((skill) => skill.id).join(',');
}, { immediate: true });

function onReorder(evt) {
    if (reordering.value) return;

    const previousIds = originalOrder.value.split(',').filter(Boolean);
    if (previousIds.length === 0 || typeof evt.oldIndex !== 'number' || typeof evt.newIndex !== 'number') return;

    const ids = [...previousIds];
    const [moved] = ids.splice(evt.oldIndex, 1);
    ids.splice(evt.newIndex, 0, moved);

    if (ids.join(',') === previousIds.join(',')) return;

    reordering.value = true;
    router.post(route('admin.data-induk.keterampilan.urutkan'), {
        keterampilan: ids,
        jenis_tes_id: props.jenisTes.id,
    }, {
        preserveScroll: true,
        onError: () => {
            toast.error('Gagal menyimpan urutan.');
            router.reload({ only: ['keterampilan'] });
        },
        onFinish: () => { reordering.value = false; },
    });
}

function openCreate() {
    creating.value = true;
    editingSkill.value = null;
    form.clearErrors();
    form.reset();
    form.jenis_tes_id = props.jenisTes.id;
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.keterampilan.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            form.jenis_tes_id = props.jenisTes.id;
        },
    });
}

function startEdit(keterampilan) {
    creating.value = false;
    editingSkill.value = keterampilan;
    editForm.clearErrors();
    editForm.reset();
    editForm.kode = keterampilan.kode;
    editForm.jenis_tes_id = props.jenisTes.id;
    editForm.nama = keterampilan.nama;
    editForm.deskripsi = keterampilan.deskripsi || '';
    editForm.aktif = !!keterampilan.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.data-induk.keterampilan.perbarui', editingSkill.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingSkill.value = null;
    creating.value = false;
    editForm.reset();
    editForm.jenis_tes_id = props.jenisTes.id;
}

async function destroy(keterampilan) {
    if (!await confirm.confirm(`Hapus keterampilan "${keterampilan.nama}"?`)) return;
    router.delete(route('admin.data-induk.keterampilan.hapus', keterampilan.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Keterampilan - ${props.jenisTes.nama}`" />
    <TataLetakDasbor
        :title="`Keterampilan - ${props.jenisTes.nama}`"
        :subtitle="`Kelola keterampilan pada ${props.jenisTes.nama}`"
    >
        <KeterampilanTable
            :keterampilan="props.keterampilan"
            :reordering="reordering"
            :back-url="route('admin.data-induk.keterampilan.daftar')"
            @create="openCreate"
            @reorder="onReorder"
            @edit="startEdit"
            @delete="destroy"
        />

        <KeterampilanFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :jenis-tes="[props.jenisTes]"
            lock-jenis-tes
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
