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
    <BaseModal
        :show="show"
        max-width="2xl"
        :closeable="false"
        aria-labelledby="soal-bank-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="soal-bank-modal-title" class="text-headline-md font-bold text-text-heading">{{ creating ? 'Tambah Bank Soal' : 'Ubah Bank Soal' }}</h2>
                        <button type="button" aria-label="Tutup form bank soal" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <form id="form-bank-soal" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <BaseFormGrid>
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
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Nama Bank Soal *</label>
                        <input type="text" v-model="form.nama" required placeholder="Bank Soal 2024"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.nama" class="text-error-red text-xs mt-1">{{ form.errors.nama }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Deskripsi</label>
                        <RichTextEditor v-model="form.deskripsi" placeholder="Jelaskan bank soal ini..." :min-height="'120px'" />
                        <p v-if="form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ form.errors.deskripsi }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <BaseToggle v-model="form.aktif" label="Bank soal aktif" />
                    </div>
                </BaseFormGrid>
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-bank-soal"
                        :processing="form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
