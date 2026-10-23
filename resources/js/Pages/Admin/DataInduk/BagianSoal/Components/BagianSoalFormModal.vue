<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    opsiKeterampilan: { type: Array, default: () => [] },
    bankFilter: { type: String, default: '' },
    lockKeterampilan: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="show" @close="emit('close')" max-width="2xl" scrollable>
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-headline-md font-bold text-text-heading">{{ creating ? 'Tambah Bagian Soal' : 'Ubah Bagian Soal' }}</h2>
                <button @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                    <IconX :size="20" />
                </button>
            </div>
            <form @submit.prevent="emit('submit')" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <DropDown
                            v-model="form.bank_soal_id"
                            :options="bankSoal"
                            label="Bank Soal *"
                            placeholder="Pilih bank"
                            option-label="nama"
                            option-value="id"
                            :disabled="creating && bankFilter !== ''"
                        />
                        <p v-if="form.errors.bank_soal_id" class="text-error-red text-xs mt-1">{{ form.errors.bank_soal_id }}</p>
                    </div>
                    <div>
                        <DropDown
                            v-model="form.keterampilan_id"
                            :options="opsiKeterampilan"
                            label="Keterampilan *"
                            placeholder="Pilih keterampilan"
                            option-label="label"
                            option-value="value"
                            :disabled="lockKeterampilan"
                            empty-message="Belum ada data keterampilan."
                        />
                        <p v-if="form.errors.keterampilan_id" class="text-error-red text-xs mt-1">{{ form.errors.keterampilan_id }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Nama Bagian Soal *</label>
                    <input type="text" v-model="form.nama" required placeholder="Bagian 1"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="form.errors.nama" class="text-error-red text-xs mt-1">{{ form.errors.nama }}</p>
                    <p v-if="creating" class="text-label-md text-text-muted mt-1">
                        Urutan otomatis ditambahkan di akhir daftar bagianSoal — ubah urutan lewat drag &amp; drop di tabel.
                    </p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Petunjuk</label>
                    <RichTextEditor v-model="form.petunjuk" placeholder="Teks arahan untuk bagianSoal ini..." :min-height="'120px'" />
                    <p v-if="form.errors.petunjuk" class="text-error-red text-xs mt-1">{{ form.errors.petunjuk }}</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.aktif" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Aktif</span>
                </label>
                <hr class="border-outline-variant/50" />
                <div class="flex gap-4">
                    <BaseButton type="submit" :disabled="form.processing" size="xl" class="flex-1">
                        {{ form.processing ? 'Menyimpan...' : (creating ? 'Simpan Bagian Soal' : 'Simpan Perubahan') }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                        Batal
                    </BaseButton>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
