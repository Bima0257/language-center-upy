<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';

const props = defineProps({
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    periodeUjian: { type: Array, default: () => [] },
    formId: { type: String, default: null },
    showSubmit: { type: Boolean, default: true },
});

const emit = defineEmits(['submit']);
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
                <input
                    v-model="props.form.dibuka_pada"
                    type="datetime-local"
                    class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
                />
                <p v-if="props.form.errors.dibuka_pada" class="mt-1 text-xs text-error-red">{{ props.form.errors.dibuka_pada }}</p>
            </div>
            <div>
                <label class="mb-2 block text-label-md font-medium text-text-heading">Ditutup Pada</label>
                <input
                    v-model="props.form.ditutup_pada"
                    type="datetime-local"
                    class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
                />
                <p v-if="props.form.errors.ditutup_pada" class="mt-1 text-xs text-error-red">{{ props.form.errors.ditutup_pada }}</p>
            </div>
        </BaseFormGrid>

        <div>
            <label class="mb-2 block text-label-md font-medium text-text-heading">Catatan</label>
            <textarea
                v-model="props.form.catatan"
                rows="4"
                placeholder="Informasi pembayaran atau ketentuan pendaftaran..."
                class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
            />
            <p v-if="props.form.errors.catatan" class="mt-1 text-xs text-error-red">{{ props.form.errors.catatan }}</p>
        </div>

        <BaseToggle v-model="props.form.aktif" label="Periode aktif" />
        <BaseButton v-if="props.showSubmit" type="submit" :disabled="props.form.processing" size="xl" class="w-full">
            {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Buat Periode Pendaftaran' : 'Simpan Perubahan') }}
        </BaseButton>
    </form>
</template>
