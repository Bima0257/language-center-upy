<script setup>
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    editing: { type: Boolean, default: false },
    form: { type: Object, required: true },
    jenisTes: { type: Object, required: true },
    submitLabel: { type: String, default: '' },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="props.show" @close="emit('close')" max-width="2xl" scrollable>
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-headline-md font-bold text-primary">{{ props.editing ? 'Ubah Draft Interpretasi' : 'Tambah Draft Interpretasi' }}</h2>
                <button type="button" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors" title="Tutup">
                    <IconX :size="20" />
                </button>
            </div>

            <form @submit.prevent="emit('submit')" class="space-y-5">
                <div class="rounded-2xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted">Jenis Tes</p>
                    <p class="text-body-md font-semibold text-primary mt-1">{{ props.jenisTes.kode }} - {{ props.jenisTes.nama }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Min Skor *</label>
                        <input type="number" v-model="props.form.skor_minimum" required min="0"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.skor_minimum" class="text-error-red text-xs mt-1">{{ props.form.errors.skor_minimum }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Max Skor *</label>
                        <input type="number" v-model="props.form.skor_maksimum" required min="0"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.skor_maksimum" class="text-error-red text-xs mt-1">{{ props.form.errors.skor_maksimum }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">CEFR *</label>
                        <input type="text" v-model="props.form.tingkat_cefr" required maxlength="10" placeholder="B1+"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.tingkat_cefr" class="text-error-red text-xs mt-1">{{ props.form.errors.tingkat_cefr }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Label *</label>
                        <input type="text" v-model="props.form.label_tingkat" required maxlength="50" placeholder="Good"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.label_tingkat" class="text-error-red text-xs mt-1">{{ props.form.errors.label_tingkat }}</p>
                    </div>
                </div>

                <div>
                    <label class="text-label-md font-medium text-primary block mb-1.5">Deskripsi</label>
                    <input type="text" v-model="props.form.deskripsi" placeholder="Opsional"
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ props.form.errors.deskripsi }}</p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="props.form.lulus" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Merupakan nilai LULUS</span>
                </label>

                <hr class="border-outline-variant/50" />
                <div class="flex gap-4">
                    <BaseButton type="submit" :disabled="props.form.processing" size="xl" class="flex-1">
                        {{ props.form.processing ? 'Menyimpan...' : (props.submitLabel || 'Tambah ke Daftar') }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                        Batal
                    </BaseButton>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
