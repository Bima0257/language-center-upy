<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';

defineProps({
    form: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoals: { type: Array, default: () => [] },
    materiSoal: { type: Array, default: () => [] },
    isMaterialAudio: { type: Boolean, default: false },
});

const emit = defineEmits(['skill-change']);
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <DropDown
                v-model="form.bank_soal_id"
                :options="bankSoal"
                label="Bank Soal *"
                placeholder="Pilih Bank Soal"
                option-label="nama"
                option-value="id"
            />
        </div>
        <div>
            <p class="text-label-md font-medium text-primary mb-2">Keterampilan *</p>
            <div class="flex flex-wrap gap-3">
                <label v-for="keterampilan in opsiKeterampilan" :key="keterampilan.value"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                       :class="form.keterampilan_id === keterampilan.value ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                    <input type="radio" name="keterampilan" :value="keterampilan.value" v-model="form.keterampilan_id"
                           @change="emit('skill-change')"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                    <span class="font-semibold" :class="form.keterampilan_id === keterampilan.value ? 'text-secondary' : 'text-primary'">{{ keterampilan.label }}</span>
                </label>
            </div>
        </div>
    </div>
    <div>
        <p class="text-label-md font-medium text-primary mb-2">Bagian Soal *</p>
        <div v-if="bagianSoals.length" class="flex flex-wrap gap-3">
            <label v-for="bagianSoal in bagianSoals" :key="bagianSoal.id"
                   class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                   :class="form.bagian_soal_id === bagianSoal.id ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                <input type="radio" name="bagianSoal" :value="bagianSoal.id" v-model="form.bagian_soal_id"
                       class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                <span class="font-semibold" :class="form.bagian_soal_id === bagianSoal.id ? 'text-secondary' : 'text-primary'">{{ bagianSoal.nama }}</span>
            </label>
        </div>
        <p v-else class="text-label-md text-text-muted">Belum ada bagianSoal untuk keterampilan ini.</p>
    </div>
    <div>
        <DropDown
            v-model="form.materi_soal_id"
            :options="materiSoal"
            label="Materi Soal"
            :placeholder="isMaterialAudio ? 'Pilih materiSoal audio (wajib)' : 'Tanpa Materi Soal'"
            option-label="judul"
            option-value="id"
            clearable
        />
        <p v-if="materiSoal.length === 0" class="text-label-md text-text-muted mt-1">
            Belum ada materi soal yang cocok untuk keterampilan &amp; bank soal ini.
        </p>
    </div>
</template>
