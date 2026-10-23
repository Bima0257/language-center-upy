<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import InterpretasiSkorBulkTable from '../../components/InterpretasiSkorBulkTable.vue';
import InterpretasiSkorFormModal from '../../components/InterpretasiSkorFormModal.vue';
import { computed, ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const MAX_ROWS = 10;

const props = defineProps({
    jenisTes: { type: Object, required: true },
});

const toast = useToast();
const interpretasiSkor = ref([]);
const showModal = ref(false);
const editingIndex = ref(null);

const form = useForm({
    interpretasi_skor: [],
});

const draftForm = useForm({
    skor_minimum: '',
    skor_maksimum: '',
    tingkat_cefr: '',
    label_tingkat: '',
    lulus: true,
    deskripsi: '',
});

const serverErrors = computed(() => Object.values(form.errors).filter(Boolean));

function resetDraft() {
    draftForm.clearErrors();
    draftForm.reset();
}

function openCreate() {
    if (interpretasiSkor.value.length >= MAX_ROWS) return;

    editingIndex.value = null;
    resetDraft();
    showModal.value = true;
}

function openEdit(index) {
    const selectedInterpretasi = interpretasiSkor.value[index];
    if (!selectedInterpretasi) return;

    editingIndex.value = index;
    draftForm.clearErrors();
    draftForm.skor_minimum = selectedInterpretasi.skor_minimum;
    draftForm.skor_maksimum = selectedInterpretasi.skor_maksimum;
    draftForm.tingkat_cefr = selectedInterpretasi.tingkat_cefr;
    draftForm.label_tingkat = selectedInterpretasi.label_tingkat;
    draftForm.lulus = selectedInterpretasi.lulus;
    draftForm.deskripsi = selectedInterpretasi.deskripsi || '';
    showModal.value = true;
}

function saveDraft() {
    draftForm.clearErrors();

    const minimumInput = String(draftForm.skor_minimum).trim();
    const maksimumInput = String(draftForm.skor_maksimum).trim();
    const tingkatCefr = draftForm.tingkat_cefr.trim();
    const labelTingkat = draftForm.label_tingkat.trim();
    const skorMinimum = Number(minimumInput);
    const skorMaksimum = Number(maksimumInput);

    if (!minimumInput || !Number.isInteger(skorMinimum) || skorMinimum < 0) {
        draftForm.setError('skor_minimum', 'Skor minimum harus berupa angka bulat minimal 0.');
        return;
    }

    if (!maksimumInput || !Number.isInteger(skorMaksimum) || skorMaksimum < skorMinimum) {
        draftForm.setError('skor_maksimum', 'Skor maksimum harus lebih besar atau sama dengan skor minimum.');
        return;
    }

    if (!tingkatCefr) {
        draftForm.setError('tingkat_cefr', 'Tingkat CEFR wajib diisi.');
        return;
    }

    if (!labelTingkat) {
        draftForm.setError('label_tingkat', 'Label tingkat wajib diisi.');
        return;
    }

    const duplicateMinimum = interpretasiSkor.value.some((interpretasi, index) =>
        index !== editingIndex.value && Number(interpretasi.skor_minimum) === skorMinimum,
    );
    if (duplicateMinimum) {
        draftForm.setError('skor_minimum', 'Skor minimum sudah ada dalam daftar.');
        return;
    }

    const row = {
        skor_minimum: skorMinimum,
        skor_maksimum: skorMaksimum,
        tingkat_cefr: tingkatCefr,
        label_tingkat: labelTingkat,
        lulus: !!draftForm.lulus,
        deskripsi: draftForm.deskripsi.trim(),
    };

    if (editingIndex.value === null) {
        interpretasiSkor.value.push(row);
    } else {
        interpretasiSkor.value.splice(editingIndex.value, 1, row);
    }

    closeModal();
}

function closeModal() {
    showModal.value = false;
    editingIndex.value = null;
    resetDraft();
}

function removeDraft(index) {
    interpretasiSkor.value.splice(index, 1);
}

function submit() {
    if (interpretasiSkor.value.length === 0) {
        toast.error('Tambahkan minimal satu interpretasi skor.');
        return;
    }

    form.clearErrors();
    form.interpretasi_skor = interpretasiSkor.value;
    form.post(route('admin.data-induk.interpretasi-skor.jenis-tes.simpan-massal', props.jenisTes.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="`Tambah Massal - ${props.jenisTes.nama}`" />
    <TataLetakDasbor
        :title="'Tambah Massal Interpretasi Skor'"
        :subtitle="`Tambahkan interpretasi skor untuk ${props.jenisTes.nama}`"
    >
        <InterpretasiSkorBulkTable
            :interpretasi-skor="interpretasiSkor"
            :max-rows="MAX_ROWS"
            :back-url="route('admin.data-induk.interpretasi-skor.jenis-tes.daftar', props.jenisTes.id)"
            @create="openCreate"
            @edit="openEdit"
            @delete="removeDraft"
        />

        <div v-if="serverErrors.length" class="mt-4 rounded-2xl border border-error-red/30 bg-error-container/30 px-5 py-4 text-error-red text-body-md">
            <p v-for="(error, index) in serverErrors" :key="index">{{ error }}</p>
        </div>

        <div class="flex justify-end mt-6">
            <BaseButton size="lg" :disabled="form.processing || interpretasiSkor.length === 0" @click="submit">
                {{ form.processing ? 'Menyimpan...' : 'Simpan Semua' }}
            </BaseButton>
        </div>

        <InterpretasiSkorFormModal
            :show="showModal"
            :editing="editingIndex !== null"
            :form="draftForm"
            :jenis-tes="props.jenisTes"
            submit-label="Tambah ke Daftar"
            @close="closeModal"
            @submit="saveDraft"
        />
    </TataLetakDasbor>
</template>
