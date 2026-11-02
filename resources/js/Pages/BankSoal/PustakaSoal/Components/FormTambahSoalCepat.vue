<script setup>
import InputPilihan from '@/Pages/BankSoal/Components/InputPilihan.vue';
import PemilihKunciJawaban from '@/Pages/BankSoal/Components/PemilihKunciJawaban.vue';
import { IconHeadphones } from '@tabler/icons-vue';

defineProps({
    form: { type: Object, required: true },
    quickIsAudio: { type: Boolean, default: false },
    optionKeys: { type: Array, default: () => [] },
    formId: { type: String, default: null },
    showActions: { type: Boolean, default: true },
});

defineEmits(['save', 'cancel']);
</script>

<template>
    <form :id="formId || undefined" @submit.prevent="$emit('save')" class="space-y-5">
        <template v-if="quickIsAudio">
            <div class="space-y-4 rounded-xl border border-pastel-purple/40 bg-pastel-purple/10 p-4">
                <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                    <IconHeadphones :size="16" class="shrink-0 text-secondary" />
                    Soal audio memakai audio materi soal ini — tanpa teks soal dan pilihan jawaban.
                </p>
                <PemilihKunciJawaban
                    v-model="form.kunci_jawaban"
                    :option-keys="optionKeys"
                    label="Pilihan Jawaban"
                    size="compact"
                />
            </div>
        </template>
        <template v-else>
            <BaseFormGrid>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-body-md font-medium text-text-heading">
                        Teks Soal <span class="text-error-red">*</span>
                    </label>
                    <BaseTextarea v-model="form.teks_soal" rows="4" required />
                    <p v-if="form.errors.teks_soal" class="mt-1 text-xs text-error-red">
                        {{ form.errors.teks_soal }}
                    </p>
                </div>

                <InputPilihan
                    class="sm:col-span-2"
                    :form="form"
                    :option-keys="optionKeys"
                    size="compact"
                />

                <PemilihKunciJawaban
                    class="sm:col-span-2"
                    v-model="form.kunci_jawaban"
                    :option-keys="optionKeys"
                    size="compact"
                />
            </BaseFormGrid>
        </template>
        <BaseFormActions
            v-if="showActions"
            :processing="form.processing"
            @cancel="$emit('cancel')"
        />
    </form>
</template>
