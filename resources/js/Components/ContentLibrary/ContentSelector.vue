<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import UserGuide from '@/Components/Shared/UserGuide.vue';

defineProps({
    examTypes: { type: Array, default: () => [] },
    filteredBanks: { type: Array, default: () => [] },
    selectedTypeId: { type: String, default: '' },
    selectedBankId: { type: String, default: '' },
    bankDisabled: { type: Boolean, default: false },
    resetDisabled: { type: Boolean, default: true },
});

defineEmits(['type-change', 'bank-change', 'reset']);
</script>

<template>
    <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="w-72">
                <DropDown
                    :model-value="selectedTypeId"
                    :options="examTypes"
                    label="Jenis Tes"
                    placeholder="Pilih jenis tes"
                    option-label="name"
                    option-value="id"
                    @change="$emit('type-change', $event)"
                />
            </div>
            <div class="w-72">
                <DropDown
                    :model-value="selectedBankId"
                    :options="filteredBanks"
                    label="Bank Soal"
                    placeholder="Pilih bank soal"
                    option-label="name"
                    option-value="id"
                    :disabled="bankDisabled"
                    @change="$emit('bank-change', $event)"
                />
            </div>
            <button @click="$emit('reset')" :disabled="resetDisabled"
                    class="px-5 py-3 border border-outline-variant rounded-2xl text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                Reset
            </button>
            <UserGuide
                title="Panduan Bank Soal"
                :steps="[
                    { title: 'Pilih Jenis Tes', desc: 'Pilih kategori tes (mis. TOEFL) untuk menyaring bank soal.' },
                    { title: 'Pilih Bank Soal', desc: 'Daftar bank sesuai jenis tes; soal baru tampil setelah bank dipilih.' },
                    { title: 'Kelola & Filter Soal', desc: 'Gunakan filter Skill, Part, dan Status untuk menemukan soal.' },
                ]"
                :rules="[
                    'Soal dikelompokkan per skill → part → materi soal (passage).',
                    'Soal baru berstatus Draf dan harus disetujui admin (Disetujui).',
                    'Hanya soal Disetujui yang bisa dipakai di ujian.',
                ]"
            />
        </div>
    </div>
</template>
