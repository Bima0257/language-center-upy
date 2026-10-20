<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import UserGuide from '@/Components/Shared/UserGuide.vue';

defineProps({
    jenisTes: { type: Array, default: () => [] },
    bankSoalTersaring: { type: Array, default: () => [] },
    jenisTesTerpilihId: { type: String, default: '' },
    bankSoalTerpilihId: { type: String, default: '' },
    bankSoalDinonaktifkan: { type: Boolean, default: false },
    resetDinonaktifkan: { type: Boolean, default: true },
});

defineEmits(['type-change', 'bank-change', 'reset']);
</script>

<template>
    <BaseCard class="mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="w-72">
                <DropDown
                    :model-value="jenisTesTerpilihId"
                    :options="jenisTes"
                    label="Jenis Tes"
                    placeholder="Pilih jenis tes"
                     option-label="nama"
                    option-value="id"
                    @change="$emit('type-change', $event)"
                />
            </div>
            <div class="w-72">
                <DropDown
                    :model-value="bankSoalTerpilihId"
                    :options="bankSoalTersaring"
                    label="Bank Soal"
                    placeholder="Pilih bank soal"
                     option-label="nama"
                    option-value="id"
                    :disabled="bankSoalDinonaktifkan"
                    @change="$emit('bank-change', $event)"
                />
            </div>
            <button @click="$emit('reset')" :disabled="resetDinonaktifkan"
                    class="px-5 py-3 border border-outline-variant rounded-2xl text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                Reset
            </button>
            <UserGuide
                title="Panduan Bank Soal"
                :steps="[
                    { judul: 'Pilih Jenis Tes', desc: 'Pilih kategori tes (mis. Tes Bahasa) untuk menyaring bank soal.' },
                    { judul: 'Pilih Bank Soal', desc: 'Daftar bank sesuai jenis tes; soal baru tampil setelah bank dipilih.' },
                    { judul: 'Kelola & Filter Soal', desc: 'Gunakan filter Keterampilan, Bagian Soal, dan Status untuk menemukan soal.' },
                ]"
                :rules="[
                    'Soal dikelompokkan per keterampilan → bagianSoal → materi soal (materiSoal).',
                    'Soal baru berstatus Draf dan harus disetujui admin (Disetujui).',
                    'Hanya soal Disetujui yang bisa dipakai di ujian.',
                ]"
            />
        </div>
    </BaseCard>
</template>
