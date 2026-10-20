<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    jenisTes: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="show" max-width="2xl" :closeable="false" scrollable>
        <div role="dialog" aria-modal="true" aria-labelledby="soal-bank-modal-title" class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 id="soal-bank-modal-title" class="text-headline-md font-bold text-primary">{{ creating ? 'Tambah Bank Soal' : 'Ubah Bank Soal' }}</h2>
                <button type="button" aria-label="Tutup form bank soal" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                    <IconX :size="20" />
                </button>
            </div>
            <form @submit.prevent="emit('submit')" class="space-y-5">
                <div>
                    <DropDown
                        v-model="form.jenis_tes_id"
                        :options="jenisTes"
                        label="Kategori Tes *"
                        placeholder="Pilih kategori tes"
                        option-label="nama"
                        option-value="id"
                    />
                    <p v-if="form.errors.jenis_tes_id" class="text-error-red text-xs mt-1">{{ form.errors.jenis_tes_id }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Nama Bank Soal *</label>
                    <input type="text" v-model="form.nama" required placeholder="Bank Soal 2024"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="form.errors.nama" class="text-error-red text-xs mt-1">{{ form.errors.nama }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Deskripsi</label>
                    <RichTextEditor v-model="form.deskripsi" placeholder="Jelaskan bank soal ini..." :min-height="'120px'" />
                    <p v-if="form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ form.errors.deskripsi }}</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.aktif" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Aktif</span>
                </label>
                <hr class="border-outline-variant/50" />
                <div class="flex gap-4">
                    <BaseButton type="submit" :disabled="form.processing" size="xl" class="flex-1">
                        {{ form.processing ? 'Menyimpan...' : (creating ? 'Simpan Bank Soal' : 'Simpan Perubahan') }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                        Batal
                    </BaseButton>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
