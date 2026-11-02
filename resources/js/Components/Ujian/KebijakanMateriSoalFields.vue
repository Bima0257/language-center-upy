<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';

defineProps({
    form: { type: Object, required: true },
});

const ketentuanOptions = [
    { id: 'required', name: 'Wajib' },
    { id: 'optional', name: 'Opsional' },
    { id: 'forbidden', name: 'Terlarang' },
];
</script>

<template>
    <div class="space-y-4 rounded-2xl border border-outline-variant/50 bg-surface-container-low/60 p-4">
        <div>
            <p class="text-body-md font-semibold text-text-heading">Aturan Materi Soal</p>
            <p class="mt-1 text-label-md leading-relaxed text-text-muted">
                Aturan ini berlaku untuk semua bank soal dalam jenis tes keterampilan ini.
                Kosongkan jika policy belum ingin dikonfigurasi.
            </p>
        </div>

        <BaseFormGrid>
            <div>
                <DropDown
                    v-model="form.ketentuan_materi"
                    :options="ketentuanOptions"
                    label="Penggunaan materi"
                    placeholder="Pilih aturan materi"
                    option-label="name"
                    option-value="id"
                    clearable
                />
                <p v-if="form.errors.ketentuan_materi" class="mt-1 text-xs text-error-red">
                    {{ form.errors.ketentuan_materi }}
                </p>
            </div>

            <div>
                <DropDown
                    v-model="form.aturan_media.text"
                    :options="ketentuanOptions"
                    label="Teks"
                    placeholder="Pilih aturan teks"
                    option-label="name"
                    option-value="id"
                    clearable
                />
                <p v-if="form.errors['aturan_media.text']" class="mt-1 text-xs text-error-red">
                    {{ form.errors['aturan_media.text'] }}
                </p>
            </div>

            <div>
                <DropDown
                    v-model="form.aturan_media.audio"
                    :options="ketentuanOptions"
                    label="Audio"
                    placeholder="Pilih aturan audio"
                    option-label="name"
                    option-value="id"
                    clearable
                />
                <p v-if="form.errors['aturan_media.audio']" class="mt-1 text-xs text-error-red">
                    {{ form.errors['aturan_media.audio'] }}
                </p>
            </div>

            <div>
                <DropDown
                    v-model="form.aturan_media.image"
                    :options="ketentuanOptions"
                    label="Gambar"
                    placeholder="Pilih aturan gambar"
                    option-label="name"
                    option-value="id"
                    clearable
                />
                <p v-if="form.errors['aturan_media.image']" class="mt-1 text-xs text-error-red">
                    {{ form.errors['aturan_media.image'] }}
                </p>
            </div>
        </BaseFormGrid>
    </div>
</template>
