<script setup>
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    editing: { type: Boolean, default: false },
    form: { type: Object, required: true },
    jenisTes: { type: Object, required: true },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="props.show"
        @close="emit('close')"
        max-width="2xl"
        aria-labelledby="interpretasi-skor-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="interpretasi-skor-modal-title" class="text-headline-md font-bold text-text-heading">{{ props.editing ? 'Ubah Interpretasi Skor' : 'Tambah Draft Interpretasi' }}</h2>
                         <button type="button" aria-label="Tutup form interpretasi skor" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <form id="form-interpretasi-skor" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <div class="rounded-2xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted">Jenis Tes</p>
                    <p class="text-body-md font-semibold text-text-heading mt-1">{{ props.jenisTes.kode }} - {{ props.jenisTes.nama }}</p>
                </div>

                <BaseFormGrid>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Min Skor *</label>
                        <input type="number" v-model="props.form.skor_minimum" required min="0"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.skor_minimum" class="text-error-red text-xs mt-1">{{ props.form.errors.skor_minimum }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Max Skor *</label>
                        <input type="number" v-model="props.form.skor_maksimum" required min="0"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.skor_maksimum" class="text-error-red text-xs mt-1">{{ props.form.errors.skor_maksimum }}</p>
                    </div>
                </BaseFormGrid>

                <BaseFormGrid>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">CEFR *</label>
                        <input type="text" v-model="props.form.tingkat_cefr" required maxlength="10" placeholder="B1+"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.tingkat_cefr" class="text-error-red text-xs mt-1">{{ props.form.errors.tingkat_cefr }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Label *</label>
                        <input type="text" v-model="props.form.label_tingkat" required maxlength="50" placeholder="Good"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.label_tingkat" class="text-error-red text-xs mt-1">{{ props.form.errors.label_tingkat }}</p>
                    </div>
                </BaseFormGrid>

                <div>
                    <label class="text-label-md font-medium text-text-heading block mb-1.5">Deskripsi</label>
                    <input type="text" v-model="props.form.deskripsi" placeholder="Opsional"
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    <p v-if="props.form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ props.form.errors.deskripsi }}</p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="props.form.lulus" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <span class="text-label-md text-text-body">Merupakan nilai LULUS</span>
                </label>
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-interpretasi-skor"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
