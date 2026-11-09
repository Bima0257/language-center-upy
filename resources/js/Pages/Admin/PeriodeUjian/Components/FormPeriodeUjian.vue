<script setup>
const props = defineProps({
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    formId: { type: String, default: null },
    showActions: { type: Boolean, default: true },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <form :id="props.formId || undefined" @submit.prevent="emit('submit')" class="space-y-5">
        <div>
            <label class="text-label-md font-medium text-text-heading block mb-2">Nama Periode</label>
            <input type="text" v-model="props.form.judul" required
                   :placeholder="props.creating ? 'Gelombang 1 — Agustus 2026' : null"
                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
            <p v-if="props.form.errors.judul" class="mt-1 text-xs text-error-red">{{ props.form.errors.judul }}</p>
        </div>
        <BaseFormGrid>
            <div>
                <label class="text-label-md font-medium text-text-heading block mb-2">Tanggal Mulai</label>
                <input type="date" v-model="props.form.tanggal_mulai" required
                       class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                <p v-if="props.form.errors.tanggal_mulai" class="mt-1 text-xs text-error-red">{{ props.form.errors.tanggal_mulai }}</p>
            </div>
            <div>
                <label class="text-label-md font-medium text-text-heading block mb-2">Tanggal Selesai</label>
                <input type="date" v-model="props.form.tanggal_selesai" required
                       class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                <p v-if="props.form.errors.tanggal_selesai" class="mt-1 text-xs text-error-red">{{ props.form.errors.tanggal_selesai }}</p>
            </div>
        </BaseFormGrid>
        <p v-if="props.creating" class="text-label-md text-text-muted">
            Periode menentukan rentang hari ujian. Sesi per hari diatur setelah periode dibuat (menu "Kelola Sesi").
        </p>
        <BaseToggle v-if="!props.creating" v-model="props.form.aktif" label="Periode aktif" />
        <BaseButton v-if="props.showActions" type="submit" :disabled="props.form.processing" size="xl" class="w-full">
            {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Buat Periode Ujian' : 'Simpan Perubahan') }}
        </BaseButton>
    </form>
</template>
