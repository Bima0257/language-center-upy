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
    daftarSkema: { type: Array, default: () => [] },
    skemaTerpilih: { type: Object, default: null },
    interpretasiSkor: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: true },
});

const confirm = useConfirm();

const showModal = ref(false);
const editingInterpretasi = ref(null);
const selectedSkema = ref(props.skemaTerpilih?.id ?? '');
const editForm = useForm({
    skema_penilaian_id: props.skemaTerpilih?.id ?? '',
    program_tes_id: props.programTes.id,
    skor_minimum: '',
    skor_maksimum: '',
    tingkat_cefr: '',
    label_tingkat: '',
    lulus: true,
    deskripsi: '',
});

const statusLabels = {
    draft: 'Draft',
    aktif: 'Aktif',
    arsip: 'Arsip',
};

function pilihSkema() {
    router.get(
        route('admin.data-induk.interpretasi-skor.program-tes.daftar', props.programTes.id),
        { skema: selectedSkema.value },
        { preserveState: true, preserveScroll: true },
    );
}

function goToBulkCreate() {
    if (props.readOnly || !props.skemaTerpilih) return;

    router.visit(route('admin.data-induk.interpretasi-skor.program-tes.tambah-massal', {
        programTes: props.programTes.id,
        skema: props.skemaTerpilih.id,
    }));
}

function startEdit(interp) {
    if (props.readOnly) return;

    editingInterpretasi.value = interp;
    editForm.reset();
    editForm.clearErrors();
    editForm.skema_penilaian_id = props.skemaTerpilih?.id ?? '';
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
    editForm.skema_penilaian_id = props.skemaTerpilih?.id ?? '';
    editForm.program_tes_id = props.programTes.id;
}

async function destroy(interp) {
    if (props.readOnly) return;

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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Versi Skema</label>
                    <select
                        v-model="selectedSkema"
                        class="min-w-56 rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-2.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
                        @change="pilihSkema"
                    >
                        <option value="" disabled>Pilih versi</option>
                        <option v-for="skema in props.daftarSkema" :key="skema.id" :value="skema.id">
                            {{ skema.nama }} — {{ statusLabels[skema.status] || skema.status }}
                        </option>
                    </select>
                </div>
            </div>

            <p v-if="props.readOnly && props.skemaTerpilih" class="mt-3 text-label-md text-text-muted">
                Versi ini tidak dapat diubah. Buat versi baru dari halaman Aturan Penilaian untuk mengubah interpretasi.
            </p>
            <p v-if="!props.skemaTerpilih" class="mt-3 text-label-md text-text-muted">
                Belum ada skema penilaian untuk program tes ini.
            </p>
        </BaseCard>

        <InterpretasiSkorTable
            :interpretasi-skor="props.interpretasiSkor"
            :program-tes="[props.programTes]"
            :read-only="props.readOnly"
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
