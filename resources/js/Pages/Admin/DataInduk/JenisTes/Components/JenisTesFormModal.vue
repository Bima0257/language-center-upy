<script setup>
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="props.show" @close="emit('close')" max-width="2xl" scrollable>
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-headline-md font-bold text-text-heading">{{ props.creating ? 'Tambah Jenis Tes' : 'Ubah Jenis Tes' }}</h2>
                <button @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                    <IconX :size="20" />
                </button>
            </div>
            <form @submit.prevent="emit('submit')" class="space-y-5">
                <div v-if="!props.creating" class="rounded-2xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted">Kode Jenis Tes</p>
                    <p class="text-body-md font-semibold text-primary mt-1">{{ props.form.kode }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Nama Jenis Tes *</label>
                    <input type="text" v-model="props.form.nama" required placeholder="Tes Bahasa"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.nama" class="text-error-red text-xs mt-1">{{ props.form.errors.nama }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Maksimum Strikes</label>
                    <input type="number" v-model="props.form.batas_pelanggaran" min="0" max="10"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.batas_pelanggaran" class="text-error-red text-xs mt-1">{{ props.form.errors.batas_pelanggaran }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Deskripsi</label>
                    <RichTextEditor v-model="props.form.deskripsi" placeholder="Jelaskan jenis tes ini..." :min-height="'120px'" />
                    <p v-if="props.form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ props.form.errors.deskripsi }}</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="props.form.aktif" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Aktif</span>
                </label>
                <hr class="border-outline-variant/50" />
                <div class="flex gap-4">
                    <BaseButton type="submit" :disabled="props.form.processing" size="xl" class="flex-1">
                        {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Simpan Jenis Tes' : 'Simpan Perubahan') }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                        Batal
                    </BaseButton>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
