<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    opsiSection: { type: Array, default: () => [] },
    partSoals: { type: Array, default: () => [] },
    materiSoal: { type: Array, default: () => [] },
});

const emit = defineEmits(['section-change']);

const materiSoalAktif = computed(() => props.materiSoal.find(
    (materiSoal) => String(materiSoal.id) === String(props.form.materi_soal_id),
) || null);
</script>

<template>
    <BaseFormGrid>
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
            <p class="text-label-md font-medium text-text-heading mb-2">Section *</p>
            <div class="flex flex-wrap gap-3">
                <label v-for="section in opsiSection" :key="section.value"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                       :class="form.section_id === section.value ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                    <input type="radio" name="section" :value="section.value" v-model="form.section_id"
                           @change="emit('section-change')"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                           <span class="font-semibold" :class="form.section_id === section.value ? 'text-secondary' : 'text-text-heading'">{{ section.label }}</span>
                </label>
            </div>
        </div>
        <div class="sm:col-span-2">
            <p class="text-label-md font-medium text-text-heading mb-2">Part Soal *</p>
            <div v-if="partSoals.length" class="flex flex-wrap gap-3">
                <label v-for="partSoal in partSoals" :key="partSoal.id"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                       :class="form.part_soal_id === partSoal.id ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                    <input type="radio" name="partSoal" :value="partSoal.id" v-model="form.part_soal_id"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                    <span class="font-semibold" :class="form.part_soal_id === partSoal.id ? 'text-secondary' : 'text-text-heading'">{{ partSoal.nama }}</span>
                </label>
            </div>
            <p v-else class="text-label-md text-text-muted">Belum ada partSoal untuk section ini.</p>
        </div>
        <div class="sm:col-span-2 rounded-2xl border border-outline-variant/50 bg-surface-container-low/60 px-4 py-3">
            <p class="text-label-md font-medium text-text-heading">Materi Soal</p>
            <p v-if="materiSoalAktif" class="text-body-md font-semibold text-text-heading mt-1">
                {{ materiSoalAktif.judul }}
            </p>
            <p v-else class="text-body-md text-text-muted mt-1">Tanpa Materi Soal</p>
            <p class="text-label-md text-text-muted mt-2">
                Materi soal tidak dapat diganti dari halaman ini. Edit judul, audio, atau gambar melalui Pustaka Soal.
            </p>
            <p v-if="form.errors.materi_soal_id" class="text-label-md text-error-red mt-2">
                {{ form.errors.materi_soal_id }}
            </p>
        </div>
    </BaseFormGrid>
</template>
