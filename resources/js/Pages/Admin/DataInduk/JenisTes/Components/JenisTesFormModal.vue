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
    <BaseModal
        :show="props.show"
        @close="emit('close')"
        max-width="2xl"
        aria-labelledby="jenis-tes-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="jenis-tes-modal-title" class="text-headline-md font-bold text-text-heading">{{ props.creating ? 'Tambah Jenis Tes' : 'Ubah Jenis Tes' }}</h2>
                         <button type="button" aria-label="Tutup form jenis tes" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <form id="form-jenis-tes" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <div v-if="!props.creating" class="rounded-2xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted">Kode Jenis Tes</p>
                    <p class="text-body-md font-semibold text-text-heading mt-1">{{ props.form.kode }}</p>
                </div>
                <BaseFormGrid>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Nama Jenis Tes *</label>
                        <input type="text" v-model="props.form.nama" required placeholder="Tes Bahasa"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.nama" class="text-error-red text-xs mt-1">{{ props.form.errors.nama }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Maksimum Strikes</label>
                        <input type="number" v-model="props.form.batas_pelanggaran" min="0" max="10"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.batas_pelanggaran" class="text-error-red text-xs mt-1">{{ props.form.errors.batas_pelanggaran }}</p>
                    </div>
                </BaseFormGrid>
                <div>
                    <label class="text-label-md font-medium text-text-heading block mb-1.5">Deskripsi</label>
                    <RichTextEditor v-model="props.form.deskripsi" placeholder="Jelaskan jenis tes ini..." :min-height="'120px'" />
                    <p v-if="props.form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ props.form.errors.deskripsi }}</p>
                </div>
                <BaseToggle v-model="props.form.aktif" label="Jenis tes aktif" />
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-jenis-tes"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
