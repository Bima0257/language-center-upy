<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramStudiBulkTable from '../components/ProgramStudiBulkTable.vue';
import ProgramStudiFormModal from '../components/ProgramStudiFormModal.vue';
import { computed, ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    fakultas: { type: Object, required: true },
});

const toast = useToast();
const programStudi = ref([]);
const showModal = ref(false);
const editingIndex = ref(null);

const form = useForm({
    program_studi: [],
});

const draftForm = useForm({
    fakultas_id: props.fakultas.id,
    nama: '',
    kode: '',
    aktif: true,
});

const serverErrors = computed(() => Object.values(form.errors).filter(Boolean));

function resetDraft() {
    draftForm.clearErrors();
    draftForm.reset();
    draftForm.fakultas_id = props.fakultas.id;
}

function openCreate() {
    if (programStudi.value.length >= 10) return;
    editingIndex.value = null;
    resetDraft();
    showModal.value = true;
}

function openEdit(index) {
    const selectedProgramStudi = programStudi.value[index];
    if (!selectedProgramStudi) return;

    editingIndex.value = index;
    draftForm.clearErrors();
    draftForm.fakultas_id = props.fakultas.id;
    draftForm.nama = selectedProgramStudi.nama;
    draftForm.kode = selectedProgramStudi.kode;
    draftForm.aktif = selectedProgramStudi.aktif;
    showModal.value = true;
}

function saveDraft() {
    draftForm.clearErrors();

    const nama = draftForm.nama.trim();
    const kode = draftForm.kode.trim();

    if (!nama) {
        draftForm.setError('nama', 'Nama program studi wajib diisi.');
        return;
    }

    if (!kode) {
        draftForm.setError('kode', 'Kode program studi wajib diisi.');
        return;
    }

    const duplicateName = programStudi.value.some((program, index) =>
        index !== editingIndex.value && program.nama.toLowerCase() === nama.toLowerCase(),
    );
    if (duplicateName) {
        draftForm.setError('nama', 'Nama program studi sudah ada dalam daftar.');
        return;
    }

    const duplicateCode = programStudi.value.some((program, index) =>
        index !== editingIndex.value && program.kode.toLowerCase() === kode.toLowerCase(),
    );
    if (duplicateCode) {
        draftForm.setError('kode', 'Kode program studi sudah ada dalam daftar.');
        return;
    }

    const row = { nama, kode, aktif: !!draftForm.aktif };
    if (editingIndex.value === null) {
        programStudi.value.push(row);
    } else {
        programStudi.value.splice(editingIndex.value, 1, row);
    }

    closeModal();
}

function closeModal() {
    showModal.value = false;
    editingIndex.value = null;
    resetDraft();
}

function removeDraft(index) {
    programStudi.value.splice(index, 1);
}

function submit() {
    if (programStudi.value.length === 0) {
        toast.error('Tambahkan minimal satu program studi.');
        return;
    }

    form.clearErrors();
    form.program_studi = programStudi.value;
    form.post(route('admin.data-induk.program-studi.fakultas.simpan-massal', props.fakultas.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="`Tambah Massal - ${props.fakultas.nama}`" />
    <TataLetakDasbor
        :title="`Tambah Massal Program Studi`"
        :subtitle="`Tambahkan program studi untuk ${props.fakultas.nama}`"
    >
        <ProgramStudiBulkTable
            :program-studi="programStudi"
            :back-url="route('admin.data-induk.program-studi.fakultas.daftar', props.fakultas.id)"
            @create="openCreate"
            @edit="openEdit"
            @delete="removeDraft"
        />

        <div v-if="serverErrors.length" class="mt-4 rounded-2xl border border-error-red/30 bg-error-container/30 px-5 py-4 text-error-red text-body-md">
            <p v-for="(error, index) in serverErrors" :key="index">{{ error }}</p>
        </div>

        <div class="flex justify-end mt-6">
            <BaseButton size="lg" :disabled="form.processing || programStudi.length === 0" @click="submit">
                {{ form.processing ? 'Menyimpan...' : 'Simpan Semua' }}
            </BaseButton>
        </div>

        <ProgramStudiFormModal
            :show="showModal"
            creating
            :form="draftForm"
            :fakultas="[props.fakultas]"
            lock-faculty
            submit-label="Tambah ke Daftar"
            @close="closeModal"
            @submit="saveDraft"
        />
    </TataLetakDasbor>
</template>
