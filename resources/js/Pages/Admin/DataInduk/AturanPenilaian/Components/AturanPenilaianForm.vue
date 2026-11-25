<script setup>
import { computed, nextTick, ref } from 'vue';
import { IconEdit } from '@tabler/icons-vue';

const props = defineProps({
    form: { type: Object, required: true },
    cancelHref: { type: String, default: null },
    defaultEditing: { type: Boolean, default: false },
    locked: { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);

const mengubahSkala = ref(props.defaultEditing);
const inputSkalaPertama = ref(null);
const skalaInputs = ref(null);
const skalaDraft = ref(salinSkalaDariForm());

const adaErrorSkala = computed(() => Boolean(
    props.form.errors.skor_mentah_maksimum
    || props.form.errors.skor_skala_minimum
    || props.form.errors.skor_skala_maksimum,
));

const tampilkanInputSkala = computed(() => mengubahSkala.value || adaErrorSkala.value);

function salinSkalaDariForm() {
    return {
        skor_mentah_maksimum: props.form.skor_mentah_maksimum,
        skor_skala_minimum: props.form.skor_skala_minimum,
        skor_skala_maksimum: props.form.skor_skala_maksimum,
    };
}

function bersihkanErrorSkala() {
    props.form.clearErrors('skor_mentah_maksimum');
    props.form.clearErrors('skor_skala_minimum');
    props.form.clearErrors('skor_skala_maksimum');
    props.form.clearErrors('tabel_konversi');
}

function mulaiUbahSkala() {
    if (props.locked) return;

    skalaDraft.value = salinSkalaDariForm();
    mengubahSkala.value = true;

    nextTick(() => inputSkalaPertama.value?.focus());
}

function simpanSkala() {
    if (props.locked) return;

    const inputSkala = skalaInputs.value?.querySelectorAll('input') ?? [];

    for (const input of inputSkala) {
        if (!input.reportValidity()) return;
    }

    const maksimumRaw = Number(skalaDraft.value.skor_mentah_maksimum);

    props.form.skor_mentah_maksimum = maksimumRaw;
    props.form.skor_skala_minimum = Number(skalaDraft.value.skor_skala_minimum);
    props.form.skor_skala_maksimum = Number(skalaDraft.value.skor_skala_maksimum);
    props.form.tabel_konversi = Object.fromEntries(
        Object.entries(props.form.tabel_konversi || {}).filter(
            ([kunci]) => Number(kunci) <= maksimumRaw,
        ),
    );

    bersihkanErrorSkala();
    mengubahSkala.value = false;
}

function batalUbahSkala() {
    skalaDraft.value = salinSkalaDariForm();
    bersihkanErrorSkala();
    mengubahSkala.value = false;
}

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
    <form class="space-y-5" @submit.prevent="emit('submit')">
        <p
            v-if="locked"
            class="rounded-xl bg-surface-container px-4 py-3 text-label-md text-text-muted"
        >
            Program tes dipakai ujian aktif — konfigurasi skor dikunci.
        </p>

        <section class="rounded-2xl border border-outline-variant/40 p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-title-md font-semibold text-text-heading">Pengaturan Skala</h3>
                    <p class="text-label-md text-text-muted">
                        Rentang skor mentah dan skala konversi untuk section ini.
                    </p>
                </div>
                <BaseButton
                    v-if="!tampilkanInputSkala"
                    type="button"
                    variant="secondary"
                    size="sm"
                    :disabled="locked"
                    @click="mulaiUbahSkala"
                >
                    <IconEdit :size="16" />
                    Ubah
                </BaseButton>
            </div>

            <div v-if="!tampilkanInputSkala" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest px-4 py-3">
                    <p class="text-label-md text-text-muted">Skor mentah maksimum</p>
                    <p class="mt-1 text-title-md font-semibold text-text-heading">{{ form.skor_mentah_maksimum }}</p>
                </div>
                <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest px-4 py-3">
                    <p class="text-label-md text-text-muted">Skor skala minimum</p>
                    <p class="mt-1 text-title-md font-semibold text-text-heading">{{ form.skor_skala_minimum }}</p>
                </div>
                <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest px-4 py-3">
                    <p class="text-label-md text-text-muted">Skor skala maksimum</p>
                    <p class="mt-1 text-title-md font-semibold text-text-heading">{{ form.skor_skala_maksimum }}</p>
                </div>
            </div>

            <div v-else ref="skalaInputs" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Skor mentah maksimum *</label>
                    <input
                        ref="inputSkalaPertama"
                        v-model.number="skalaDraft.skor_mentah_maksimum"
                        type="number"
                        min="1"
                        max="500"
                        required
                        :disabled="locked"
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                    />
                    <p v-if="form.errors.skor_mentah_maksimum" class="mt-1 text-xs text-error-red">{{ form.errors.skor_mentah_maksimum }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Skor skala minimum *</label>
                    <input
                        v-model.number="skalaDraft.skor_skala_minimum"
                        type="number"
                        min="0"
                        max="1000"
                        required
                        :disabled="locked"
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                    />
                    <p v-if="form.errors.skor_skala_minimum" class="mt-1 text-xs text-error-red">{{ form.errors.skor_skala_minimum }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Skor skala maksimum *</label>
                    <input
                        v-model.number="skalaDraft.skor_skala_maksimum"
                        type="number"
                        min="1"
                        max="1000"
                        required
                        :disabled="locked"
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                    />
                    <p v-if="form.errors.skor_skala_maksimum" class="mt-1 text-xs text-error-red">{{ form.errors.skor_skala_maksimum }}</p>
                </div>
            </div>

            <div v-if="tampilkanInputSkala" class="mt-4 flex flex-wrap justify-end gap-2">
                <BaseButton
                    type="button"
                    variant="secondary"
                    size="sm"
                    :disabled="locked"
                    @click="batalUbahSkala"
                >
                    Batal
                </BaseButton>
                <BaseButton
                    type="button"
                    size="sm"
                    :disabled="locked"
                    @click="simpanSkala"
                >
                    Simpan
                </BaseButton>
            </div>
        </section>

        <div>
            <div class="mb-2 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-title-md font-semibold text-text-heading">Tabel Konversi</h3>
                    <p class="text-label-md text-text-muted">
                        Raw (skor mentah) adalah jumlah jawaban benar pada section ini. Isi skor skala untuk setiap jumlah jawaban benar.
                    </p>
                    <p v-if="tampilkanInputSkala" class="text-label-md text-text-muted">
                        Simpan pengaturan skala untuk mengubah tabel konversi.
                    </p>
                </div>
                <span class="rounded-full bg-pastel-purple px-3 py-1 text-label-md font-medium text-text-heading">
                    {{ rawScores.length }} nilai
                </span>
            </div>
            <div class="rounded-2xl border border-outline-variant/40">
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
                            :disabled="locked || tampilkanInputSkala"
                            class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-2 py-1.5 text-label-md text-text-body focus:border-secondary focus:outline-none"
                            @input="updateConversion(rawScore, $event)"
                        />
                    </div>
                </div>
            </div>
            <p v-if="form.errors.tabel_konversi" class="mt-1 text-xs text-error-red">{{ form.errors.tabel_konversi }}</p>
        </div>

        <BaseFormActions
            :processing="form.processing"
            :disabled="locked || tampilkanInputSkala"
            :cancel-href="cancelHref"
        />
    </form>
</template>
