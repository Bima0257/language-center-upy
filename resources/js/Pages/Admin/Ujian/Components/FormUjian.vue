<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';

const props = defineProps({
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    jenisTes: { type: Array, default: () => [] },
    bankSoalTersaring: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit']);

const modeOptions = [
    { id: 'tryout', name: 'Try Out' },
    { id: 'official', name: 'Ujian Resmi' },
];
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <div>
            <DropDown
                v-model="props.form.jenis_tes_id"
                :options="props.jenisTes"
                label="Jenis Tes"
                placeholder="Pilih jenis tes"
                option-label="nama"
                option-value="id"
            />
            <p v-if="props.form.errors.jenis_tes_id" class="text-error-red text-xs mt-1">{{ props.form.errors.jenis_tes_id }}</p>
        </div>

        <div v-if="props.creating && props.form.jenis_tes_id">
            <DropDown
                v-model="props.form.bank_soal_id"
                :options="props.bankSoalTersaring"
                label="Bank Soal *"
                placeholder="Pilih bank soal"
                option-label="nama"
                option-value="id"
                empty-message="Belum ada bank soal aktif untuk jenis tes ini."
            />
            <p v-if="props.form.errors.bank_soal_id" class="text-error-red text-xs mt-1">{{ props.form.errors.bank_soal_id }}</p>
        </div>

        <div>
            <label class="text-label-md font-medium text-primary block mb-2">{{ props.creating ? 'Judul Soal Ujian' : 'Judul Ujian' }}</label>
            <input type="text" v-model="props.form.judul" required
                   :placeholder="props.creating ? 'Tes Bahasa 2026' : null"
                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
            <p v-if="props.creating && props.form.errors.judul" class="text-error-red text-xs mt-1">{{ props.form.errors.judul }}</p>
        </div>

        <div>
            <label class="text-label-md font-medium text-primary block mb-2">Deskripsi</label>
            <BaseTextarea v-model="props.form.deskripsi" rows="3"
                          :placeholder="props.creating ? 'Deskripsi ujian...' : null" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <DropDown
                    v-model="props.form.mode"
                    :options="modeOptions"
                    label="Tipe"
                    option-label="name"
                    option-value="id"
                />
            </div>
            <div>
                <label class="text-label-md font-medium text-primary block mb-2">Durasi (menit)</label>
                <input type="number" v-model="props.form.durasi_menit" required min="1"
                       class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
            </div>
        </div>

        <div v-if="!props.creating" class="flex items-center gap-2">
            <input type="checkbox" id="aktif" v-model="props.form.aktif"
                   class="rounded border-outline-variant text-primary focus:ring-secondary" />
            <label for="aktif" class="text-text-body text-body-md">Aktif</label>
        </div>

        <BaseButton
            type="submit"
            :disabled="props.form.processing || (props.creating && !props.form.bank_soal_id)"
            size="xl"
            class="w-full"
        >
            {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Buat Soal Ujian' : 'Simpan Perubahan') }}
        </BaseButton>
    </form>
</template>
