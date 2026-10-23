<script setup>
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="props.show" @close="emit('close')" max-width="lg">
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-headline-md font-bold text-text-heading">{{ props.creating ? 'Tambah Fakultas' : 'Ubah Fakultas' }}</h2>
                <button type="button" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors" title="Tutup">
                    <IconX :size="20" />
                </button>
            </div>

            <form @submit.prevent="emit('submit')" class="space-y-5">
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Nama Fakultas *</label>
                    <input type="text" v-model="props.form.nama" required placeholder="Fakultas Teknik"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.nama" class="text-error-red text-xs mt-1">{{ props.form.errors.nama }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Kode *</label>
                    <input type="text" v-model="props.form.kode" required placeholder="FT"
                           class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.kode" class="text-error-red text-xs mt-1">{{ props.form.errors.kode }}</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="props.form.aktif" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Aktif</span>
                </label>
                <hr class="border-outline-variant/50" />
                <div class="flex gap-4">
                    <BaseButton type="submit" :disabled="props.form.processing" size="xl" class="flex-1">
                        {{ props.form.processing ? 'Menyimpan...' : (props.creating ? 'Simpan Fakultas' : 'Simpan Perubahan') }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                        Batal
                    </BaseButton>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
