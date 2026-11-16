<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import SectionFormModal from '@/Components/Ujian/SectionFormModal.vue';
import SectionTable from '../Components/SectionTable.vue';
import { computed, ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    programTes: { type: Object, required: true },
    section: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();
const page = usePage();

const form = useForm({
    program_tes_id: props.programTes.id,
    kode: '',
    nama: '',
    deskripsi: '',
    aktif: true,
    ketentuan_materi: '',
    aturan_media: { text: '', audio: '', image: '' },
});

const editForm = useForm({
    program_tes_id: props.programTes.id,
    kode: '',
    nama: '',
    deskripsi: '',
    aktif: true,
    ketentuan_materi: '',
    aturan_media: { text: '', audio: '', image: '' },
});

const showModal = ref(false);
const creating = ref(false);
const editingSection = ref(null);
const reordering = ref(false);
const originalOrder = ref('');

const modalForm = computed(() => creating.value ? form : editForm);
const canManageSection = computed(() =>
    [
        'admin.data-induk.section.simpan',
        'admin.data-induk.section.urutkan',
        'admin.data-induk.section.perbarui',
        'admin.data-induk.section.hapus',
    ].some(permission => (page.props.auth?.permissions || []).includes(permission)),
);
const canCreateSection = computed(() =>
    canManageSection.value && props.programTes.aktif === true,
);

function policyForSection(sectionId) {
    return props.kebijakanMateriSoal.find((policy) =>
        String(policy.section_id) === String(sectionId),
    ) || null;
}

watch(() => props.section, (list) => {
    originalOrder.value = list.map((section) => section.id).join(',');
}, { immediate: true });

function onReorder(evt) {
    if (!canManageSection.value || reordering.value) return;

    const previousIds = originalOrder.value.split(',').filter(Boolean);
    if (previousIds.length === 0 || typeof evt.oldIndex !== 'number' || typeof evt.newIndex !== 'number') return;

    const ids = [...previousIds];
    const [moved] = ids.splice(evt.oldIndex, 1);
    ids.splice(evt.newIndex, 0, moved);

    if (ids.join(',') === previousIds.join(',')) return;

    reordering.value = true;
    router.post(route('admin.data-induk.section.urutkan'), {
        section: ids,
        program_tes_id: props.programTes.id,
    }, {
        preserveScroll: true,
        onError: () => {
            toast.error('Gagal menyimpan urutan.');
            router.reload({ only: ['section'] });
        },
        onFinish: () => { reordering.value = false; },
    });
}

function openCreate() {
    if (!canCreateSection.value) return;

    creating.value = true;
    editingSection.value = null;
    form.clearErrors();
    form.reset();
    form.program_tes_id = props.programTes.id;
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.section.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            form.program_tes_id = props.programTes.id;
        },
    });
}

function startEdit(section) {
    if (!canManageSection.value) return;

    creating.value = false;
    editingSection.value = section;
    editForm.clearErrors();
    editForm.reset();
    editForm.kode = section.kode;
    editForm.program_tes_id = props.programTes.id;
    editForm.nama = section.nama;
    editForm.deskripsi = section.deskripsi || '';
    editForm.aktif = !!section.aktif;
    const policy = policyForSection(section.id);
    editForm.ketentuan_materi = policy?.ketentuan_materi || '';
    editForm.aturan_media = {
        text: policy?.aturan_media?.text || '',
        audio: policy?.aturan_media?.audio || '',
        image: policy?.aturan_media?.image || '',
    };
    showModal.value = true;
}

function saveEdit() {
    if (!canManageSection.value || !editingSection.value) return;

    editForm.put(route('admin.data-induk.section.perbarui', editingSection.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingSection.value = null;
    creating.value = false;
    editForm.reset();
    editForm.program_tes_id = props.programTes.id;
}

async function destroy(section) {
    if (!canManageSection.value) return;

    if (!await confirm.confirm(`Hapus section "${section.nama}"?`)) return;
    router.delete(route('admin.data-induk.section.hapus', section.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Section - ${props.programTes.nama}`" />
    <TataLetakDasbor
        :title="`Section - ${props.programTes.nama}`"
        :subtitle="`Kelola section pada ${props.programTes.nama}`"
        :back-href="route('admin.data-induk.section.daftar')"
    >
        <SectionTable
            :section="props.section"
            :reordering="reordering"
            :can-manage="canManageSection"
            :can-create="canCreateSection"
            @create="openCreate"
            @reorder="onReorder"
            @edit="startEdit"
            @delete="destroy"
        />

        <SectionFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :program-tes="[props.programTes]"
            lock-program-tes
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
