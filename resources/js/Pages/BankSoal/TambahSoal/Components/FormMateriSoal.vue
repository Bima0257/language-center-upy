<script setup>
import { computed } from 'vue';
import { IconBook2, IconCheck, IconFileDescription, IconHeadphones } from '@tabler/icons-vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import MediaMateriSoal from './MediaMateriSoal.vue';

const props = defineProps({
    mode: { type: String, default: 'none' },
    form: { type: Object, required: true },
    policy: { type: Object, default: null },
    hasListeningQuestion: { type: Boolean, default: false },
});

const emit = defineEmits(['update:mode']);

const selectedMode = computed({
    get: () => props.mode,
    set: value => emit('update:mode', value),
});

const modeOptions = computed(() => {
    const options = [];

    if (props.policy?.ketentuan_materi !== 'required') {
        options.push({
            value: 'none',
            title: 'Tanpa Materi Soal',
            description: 'Soal berdiri sendiri tanpa bacaan atau audio pendamping.',
            icon: IconFileDescription,
        });
    }

    if (props.policy?.ketentuan_materi !== 'forbidden') {
        options.push({
            value: 'new',
            title: 'Buat Materi Soal Baru',
            description: props.hasListeningQuestion
                ? 'Gunakan audio pendamping sesuai policy keterampilan ini.'
                : 'Buat materi yang dapat digunakan bersama soal dalam batch ini.',
            icon: props.hasListeningQuestion ? IconHeadphones : IconBook2,
        });
    }

    return options;
});
</script>

<template>
    <div class="space-y-5">
        <div>
            <BaseFieldLabel>Pilih sumber materi</BaseFieldLabel>
            <p class="text-body-md text-text-muted -mt-0.5">
                Tentukan sumber materi sesuai policy keterampilan pada jenis tes ini.
            </p>
        </div>

        <div
            v-if="!policy"
            class="rounded-2xl border border-warning/30 bg-warning-container/30 px-4 py-3 text-label-md leading-relaxed text-on-warning-container"
        >
            Keterampilan ini belum memiliki konfigurasi aturan materi soal. Atur policy terlebih dahulu di Data Induk Keterampilan.
        </div>

        <div
            class="grid grid-cols-1 gap-3 md:grid-cols-2"
            role="radiogroup"
            aria-label="Sumber materi soal"
        >
            <label
                v-for="option in modeOptions"
                :key="option.value"
                role="radio"
                :aria-checked="mode === option.value"
                class="group relative flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-all"
                :class="mode === option.value
                    ? 'border-secondary bg-secondary/10 shadow-sm'
                    : 'border-outline-variant/60 bg-surface-white hover:border-secondary/60 hover:bg-surface-container-low'"
            >
                <input
                    v-model="selectedMode"
                    type="radio"
                    name="materi-soal-mode"
                    :value="option.value"
                    class="sr-only"
                />
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors"
                    :class="mode === option.value ? 'bg-secondary text-on-secondary' : 'bg-surface-container-low text-secondary'"
                >
                    <component :is="option.icon" :size="20" stroke="1.8" />
                </span>
                <span class="min-w-0 flex-1 pr-5">
                    <span class="block text-body-md font-semibold text-text-heading">{{ option.title }}</span>
                    <span class="mt-1 block text-label-md leading-relaxed text-text-muted">{{ option.description }}</span>
                </span>
                <IconCheck
                    v-if="mode === option.value"
                    :size="18"
                    class="absolute right-4 top-4 text-secondary"
                    stroke="2.5"
                />
            </label>
        </div>

        <div
            v-if="mode === 'none' && policy"
            class="flex items-start gap-3 rounded-2xl border border-outline-variant/50 bg-surface-container-low/60 px-4 py-3"
        >
            <IconFileDescription :size="18" class="mt-0.5 shrink-0 text-text-muted" />
            <p class="text-label-md leading-relaxed text-text-muted">
                Soal akan dibuat tanpa materi pendamping. Pilihan ini cocok untuk soal yang sudah memiliki konteks di dalam teksnya sendiri.
            </p>
        </div>

        <div
            v-else-if="mode === 'new' && policy"
            class="rounded-2xl border border-secondary/30 bg-surface-container-low/60 p-4 md:p-5"
        >
            <div class="flex flex-col gap-3 border-b border-outline-variant/50 pb-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-secondary">
                        <IconHeadphones v-if="hasListeningQuestion" :size="20" stroke="1.8" />
                        <IconBook2 v-else :size="20" stroke="1.8" />
                    </span>
                    <div>
                        <h3 class="text-body-md font-semibold text-text-heading">Detail Materi Baru</h3>
                        <p class="mt-1 text-label-md text-text-muted">
                            {{ hasListeningQuestion ? 'Materi audio untuk soal listening.' : 'Materi bacaan untuk soal reading.' }}
                        </p>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-secondary/10 px-3 py-1 text-label-md font-semibold text-secondary">
                    {{ hasListeningQuestion ? 'Listening' : 'Reading' }}
                </span>
            </div>

            <div class="mt-5">
                <BaseFieldLabel for="materi-baru-judul">Judul Materi Soal <span class="text-error-red">*</span></BaseFieldLabel>
                <BaseInput
                    id="materi-baru-judul"
                    v-model="form.materi_baru_judul"
                    placeholder="Contoh: The History of Renewable Energy"
                    required
                    :error="!!form.errors.materi_baru_judul"
                />
                <p v-if="form.errors.materi_baru_judul" class="mt-1.5 text-xs text-error-red">
                    {{ form.errors.materi_baru_judul }}
                </p>
            </div>

            <MediaMateriSoal :form="form" :policy="policy" />
        </div>

    </div>
</template>
