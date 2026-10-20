<script setup>
import { computed } from 'vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconFileDescription } from '@tabler/icons-vue';
import MediaMateriSoal from './MediaMateriSoal.vue';

const props = defineProps({
    mode: { type: String, default: 'none' },
    form: { type: Object, required: true },
    materiSoal: { type: Array, default: () => [] },
    hasListeningQuestion: { type: Boolean, default: false },
});

const emit = defineEmits(['update:mode']);

const selectedMode = computed({
    get: () => props.mode,
    set: value => emit('update:mode', value),
});
</script>

<template>
    <div>
        <p class="text-label-md font-medium text-primary mb-2">Materi Soal <span class="text-text-muted">(Listening wajib; Reading opsional)</span></p>
        <div class="flex gap-3">
            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                   :class="mode === 'none' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                <input v-model="selectedMode" type="radio" value="none" class="accent-secondary" />
                <span class="text-label-md text-text-body">Tanpa Materi Soal</span>
            </label>
            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                   :class="mode === 'existing' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                <input v-model="selectedMode" type="radio" value="existing" class="accent-secondary" />
                <span class="text-label-md text-text-body">Pilih Materi Soal</span>
            </label>
            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                   :class="mode === 'new' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                <input v-model="selectedMode" type="radio" value="new" class="accent-secondary" />
                <span class="text-label-md text-text-body">Buat Materi Soal Baru</span>
            </label>
        </div>
    </div>

    <div v-if="mode === 'existing'" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-4">
        <div class="flex items-center gap-2">
            <IconFileDescription :size="18" class="text-secondary" />
            <p class="text-label-md font-semibold text-primary">Pilih Materi Soal</p>
        </div>
        <div v-if="materiSoal.length">
            <DropDown
                v-model="form.materi_soal_id"
                :options="materiSoal"
                label="Materi Soal *"
                placeholder="Pilih materi soal"
                option-label="judul"
                option-value="id"
            />
            <p v-if="form.errors.materi_soal_id" class="text-error-red text-xs mt-1">{{ form.errors.materi_soal_id }}</p>
        </div>
        <p v-else class="text-label-md text-text-muted">
            Belum ada materi soal yang cocok untuk keterampilan ini di bank soal terpilih. Gunakan "Buat Materi Soal Baru".
        </p>
    </div>

    <div v-if="mode === 'new'" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-4">
        <div class="flex items-center gap-2">
            <IconFileDescription :size="18" class="text-secondary" />
            <p class="text-label-md font-semibold text-primary">Materi Soal Baru</p>
        </div>
        <div>
            <label class="text-label-md font-medium text-primary block mb-1.5">Judul Materi Soal <span class="text-error-red">*</span></label>
             <input type="text" v-model="form.materi_baru_judul" required placeholder="Judul bacaan"
                   class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
             <p v-if="form.errors.materi_baru_judul" class="text-error-red text-xs mt-1">{{ form.errors.materi_baru_judul }}</p>
        </div>

        <MediaMateriSoal :form="form" :has-listening-question="hasListeningQuestion" />
    </div>
</template>
