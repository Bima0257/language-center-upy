<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import FormUjian from '@/Pages/Admin/Ujian/Components/FormUjian.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    jenisTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
});

const form = useForm({
    jenis_tes_id: '',
    bank_soal_id: '',
    judul: '',
    deskripsi: '',
    mode: 'tryout',
    durasi_menit: 160,
});

const bankSoalTersaring = computed(() => {
    if (!form.jenis_tes_id) return [];
    return props.bankSoal.filter(
        (b) => String(b.jenis_tes_id) === String(form.jenis_tes_id) && b.aktif,
    );
});

watch(() => form.jenis_tes_id, () => {
        form.bank_soal_id = '';
});

function submit() {
    form.post(route('admin.ujian.simpan'));
}
</script>

<template>
    <Head title="Buat Soal Ujian" />
    <TataLetakDasbor title="Buat Soal Ujian" subtitle="Buat ujian baru dari bank soal yang tersedia">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <FormUjian
                    :form="form"
                    :jenis-tes="jenisTes"
                    :bank-soal-tersaring="bankSoalTersaring"
                    creating
                    @submit="submit"
                />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
