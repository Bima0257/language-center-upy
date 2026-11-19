<script setup>
import { computed } from 'vue';
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    rule: { type: Object, default: null },
});

const emit = defineEmits(['close', 'submit']);

const rawScores = computed(() => {
    const maximum = Math.min(Math.max(Number(props.form.skor_mentah_maksimum) || 0, 0), 500);

    return Array.from({ length: maximum + 1 }, (_, index) => index);
});

function conversionValue(rawScore) {
    return props.form.tabel_konversi?.[String(rawScore)] ?? '';
}

function updateConversion(rawScore, event) {
    const value = event.target.value;

    props.form.tabel_konversi = {
        ...(props.form.tabel_konversi || {}),
        [String(rawScore)]: value === '' ? '' : Number(value),
    };
}
</script>

<template>
    <BaseModal
        :show="show"
        max-width="4xl"
        scrollable
        aria-labelledby="aturan-penilaian-modal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h2 id="aturan-penilaian-modal-title" class="text-title-lg font-semibold text-text-heading">
                            Aturan {{ rule?.nama_section || 'Penilaian' }}
                        </h2>
                        <p class="text-label-md text-text-muted">{{ rule?.kode_section }}</p>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup form aturan penilaian"
                        class="p-2 text-text-muted transition-colors hover:text-text-heading"
                        @click="emit('close')"
                    >
                        <IconX :size="20" />
                    </button>
                </div>
            </BaseModalHeader>
        </template>

        <form id="form-aturan-penilaian" class="space-y-5 px-6 py-5" @submit.prevent="emit('submit')">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Skor mentah maksimum *</label>
                    <input
                        v-model.number="form.skor_mentah_maksimum"
                        type="number"
                        min="1"
                        max="500"
                        required
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                    />
                    <p v-if="form.errors.skor_mentah_maksimum" class="mt-1 text-xs text-error-red">{{ form.errors.skor_mentah_maksimum }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Skor skala minimum *</label>
                    <input
                        v-model.number="form.skor_skala_minimum"
                        type="number"
                        min="0"
                        max="1000"
                        required
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                    />
                    <p v-if="form.errors.skor_skala_minimum" class="mt-1 text-xs text-error-red">{{ form.errors.skor_skala_minimum }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Skor skala maksimum *</label>
                    <input
                        v-model.number="form.skor_skala_maksimum"
                        type="number"
                        min="1"
                        max="1000"
                        required
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                    />
                    <p v-if="form.errors.skor_skala_maksimum" class="mt-1 text-xs text-error-red">{{ form.errors.skor_skala_maksimum }}</p>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-title-md font-semibold text-text-heading">Tabel Konversi</h3>
                        <p class="text-label-md text-text-muted">
                            Raw (skor mentah) adalah jumlah jawaban benar pada section ini. Isi skor skala untuk setiap jumlah jawaban benar.
                        </p>
                    </div>
                    <span class="rounded-full bg-pastel-purple px-3 py-1 text-label-md font-medium text-text-heading">
                        {{ rawScores.length }} nilai
                    </span>
                </div>
                <div class="max-h-80 overflow-y-auto rounded-2xl border border-outline-variant/40">
                    <div class="grid grid-cols-2 gap-px bg-outline-variant/30 sm:grid-cols-3 lg:grid-cols-4">
                        <div
                            v-for="rawScore in rawScores"
                            :key="rawScore"
                            class="grid grid-cols-[1fr_1.2fr] items-center gap-2 bg-surface-white px-3 py-2"
                        >
                            <label :for="`konversi-${rawScore}`" class="text-label-md text-text-muted">
                                Raw {{ rawScore }}
                            </label>
                            <input
                                :id="`konversi-${rawScore}`"
                                :value="conversionValue(rawScore)"
                                type="number"
                                min="0"
                                :max="form.skor_skala_maksimum"
                                placeholder="Skor"
                                :aria-label="`Skor skala untuk Raw ${rawScore} (jumlah jawaban benar)`"
                                required
                                class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-2 py-1.5 text-label-md text-text-body focus:border-secondary focus:outline-none"
                                @input="updateConversion(rawScore, $event)"
                            />
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.tabel_konversi" class="mt-1 text-xs text-error-red">{{ form.errors.tabel_konversi }}</p>
            </div>
        </form>

        <template #footer>
            <BaseModalFooter>
                <div class="px-6 pb-3">
                    <BaseFormActions
                        form="form-aturan-penilaian"
                        :processing="form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
