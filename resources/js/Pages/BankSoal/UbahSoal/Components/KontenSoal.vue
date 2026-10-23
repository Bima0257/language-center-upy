<script setup>
import InputPilihan from '@/Pages/BankSoal/Components/InputPilihan.vue';
import PemilihKunciJawaban from '@/Pages/BankSoal/Components/PemilihKunciJawaban.vue';
import { IconInfoCircle } from '@tabler/icons-vue';

defineProps({
    form: { type: Object, required: true },
    optionKeys: { type: Array, default: () => ['A', 'B', 'C', 'D'] },
    isMaterialAudio: { type: Boolean, default: false },
    selectedPassage: { type: Object, default: null },
});
</script>

<template>
    <!-- MODE LISTENING: audio dari materiSoal -->
    <template v-if="isMaterialAudio">
        <div class="bg-pastel-purple/10 border border-pastel-purple/40 rounded-2xl p-5 space-y-4">
            <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                Soal, materi, dan pilihan jawaban berada di audio materiSoal. Peserta hanya memilih A/B/C/D.
            </p>
            <p v-if="!selectedPassage?.url_audio" class="text-label-md text-error-red">
                Soal listening wajib memakai materiSoal yang memiliki audio.
            </p>
            <p v-else class="text-label-md text-text-body">
                Audio: {{ selectedPassage.judul }}
            </p>
            <PemilihKunciJawaban v-model="form.kunci_jawaban" :option-keys="optionKeys" />
        </div>
    </template>

    <!-- MODE TEKS -->
    <template v-else>
        <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
            <BaseTextarea v-model="form.teks_soal" rows="3" required /></div>

        <InputPilihan :form="form" :option-keys="optionKeys" />

        <PemilihKunciJawaban v-model="form.kunci_jawaban" :option-keys="optionKeys" />
    </template>
</template>
