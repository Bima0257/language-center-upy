<script setup>
import { computed } from 'vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';

const props = defineProps({
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    periodeUjian: { type: Array, default: () => [] },
    formId: { type: String, default: null },
    showSubmit: { type: Boolean, default: true },
});

const emit = defineEmits(['submit']);

const timeInputClass = 'w-full rounded-2xl border bg-surface-container-lowest px-4 py-3.5 text-body-md text-text-body focus:outline-none disabled:cursor-not-allowed disabled:opacity-50';

function datePart(value) {
    return value ? String(value).slice(0, 10) : '';
}

function timePart(value) {
    if (!value || String(value).length < 16) return '';

    return String(value).slice(11, 16);
}

function combineDateTime(tanggal, jam) {
    if (!tanggal) return '';

    return `${tanggal}T${jam || '00:00'}`;
}

const dibukaTanggal = computed({
    get: () => datePart(props.form.dibuka_pada),
    set: value => {
        props.form.dibuka_pada = combineDateTime(value, timePart(props.form.dibuka_pada));
    },
});

const dibukaJam = computed({
    get: () => timePart(props.form.dibuka_pada),
    set: value => {
        const tanggal = datePart(props.form.dibuka_pada);

        if (!tanggal) return;

        props.form.dibuka_pada = combineDateTime(tanggal, value);
    },
});

const ditutupTanggal = computed({
    get: () => datePart(props.form.ditutup_pada),
    set: value => {
        props.form.ditutup_pada = combineDateTime(value, timePart(props.form.ditutup_pada));
    },
});

const ditutupJam = computed({
    get: () => timePart(props.form.ditutup_pada),
    set: value => {
        const tanggal = datePart(props.form.ditutup_pada);

        if (!tanggal) return;

        props.form.ditutup_pada = combineDateTime(tanggal, value);
    },
});

const jamTutupMinimal = computed(() => {
    const tanggalBuka = datePart(props.form.dibuka_pada);
    const tanggalTutup = datePart(props.form.ditutup_pada);

    if (!tanggalBuka || tanggalBuka !== tanggalTutup) return '';

    return timePart(props.form.dibuka_pada);
});
</script>

<template>
    <form :id="props.formId" class="space-y-5" @submit.prevent="emit('submit')">
        <DropDown
            v-if="props.creating"
            v-model="props.form.periode_ujian_id"
            :options="props.periodeUjian"
            label="Periode Ujian"
            placeholder="Pilih periode ujian"
            option-label="judul"
            option-value="id"
        />
        <p v-if="props.form.errors.periode_ujian_id" class="text-xs text-error-red">{{ props.form.errors.periode_ujian_id }}</p>

        <div>
            <label class="mb-2 block text-label-md font-medium text-text-heading">Nama Periode Pendaftaran</label>
            <input
                v-model="props.form.nama"
                type="text"
                placeholder="Gelombang Pendaftaran Agustus 2026"
                class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
            />
            <p v-if="props.form.errors.nama" class="mt-1 text-xs text-error-red">{{ props.form.errors.nama }}</p>
        </div>

        <BaseFormGrid>
            <div>
                <label class="mb-2 block text-label-md font-medium text-text-heading">Dibuka Pada</label>
                <div class="grid grid-cols-[minmax(0,1fr)_7rem] gap-2">
                    <BaseDatePicker
                        v-model="dibukaTanggal"
                        placeholder="Tanggal dibuka"
                        required
                        :error="Boolean(props.form.errors.dibuka_pada)"
                    />
                    <input
                        v-model="dibukaJam"
                        type="time"
                        required
                        aria-label="Jam dibuka"
                        :disabled="!dibukaTanggal"
                        :class="[
                            timeInputClass,
                            props.form.errors.dibuka_pada
                                ? 'border-error-red focus:border-error-red'
                                : 'border-outline-variant focus:border-secondary',
                        ]"
                    />
                </div>
                <p v-if="props.form.errors.dibuka_pada" class="mt-1 text-xs text-error-red">
                    {{ props.form.errors.dibuka_pada }}
                </p>
            </div>
            <div>
                <label class="mb-2 block text-label-md font-medium text-text-heading">Ditutup Pada</label>
                <div class="grid grid-cols-[minmax(0,1fr)_7rem] gap-2">
                    <BaseDatePicker
                        v-model="ditutupTanggal"
                        placeholder="Tanggal ditutup"
                        required
                        :min="dibukaTanggal"
                        :error="Boolean(props.form.errors.ditutup_pada)"
                    />
                    <input
                        v-model="ditutupJam"
                        type="time"
                        required
                        aria-label="Jam ditutup"
                        :min="jamTutupMinimal"
                        :disabled="!ditutupTanggal"
                        :class="[
                            timeInputClass,
                            props.form.errors.ditutup_pada
                                ? 'border-error-red focus:border-error-red'
                                : 'border-outline-variant focus:border-secondary',
                        ]"
                    />
                </div>
                <p v-if="props.form.errors.ditutup_pada" class="mt-1 text-xs text-error-red">
                    {{ props.form.errors.ditutup_pada }}
                </p>
            </div>
        </BaseFormGrid>

        <div>
            <label class="mb-2 block text-label-md font-medium text-text-heading">Catatan</label>
            <RichTextEditor
                v-model="props.form.catatan"
                placeholder="Informasi pembayaran atau ketentuan pendaftaran..."
                :min-height="'120px'"
            />
            <p v-if="props.form.errors.catatan" class="mt-1 text-xs text-error-red">{{ props.form.errors.catatan }}</p>
        </div>

        <BaseToggle v-model="props.form.aktif" label="Periode aktif" />
        <BaseButton v-if="props.showSubmit" type="submit" :disabled="props.form.processing" size="xl" class="w-full">
            {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Buat Periode Pendaftaran' : 'Simpan Perubahan') }}
        </BaseButton>
    </form>
</template>
