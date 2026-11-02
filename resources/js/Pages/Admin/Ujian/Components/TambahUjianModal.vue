<script setup>
import FormUjian from './FormUjian.vue';
import { IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: true },
    form: { type: Object, required: true },
    jenisTes: { type: Array, default: () => [] },
    bankSoalTersaring: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="show"
        max-width="2xl"
        aria-labelledby="ujian-modal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="ujian-modal-title" class="text-headline-md font-bold text-text-heading">
                            {{ creating ? 'Tambah Ujian' : 'Ubah Ujian' }}
                        </h2>
                        <button
                            type="button"
                            aria-label="Tutup form ujian"
                            class="p-2 text-text-muted transition-colors hover:text-primary"
                            @click="emit('close')"
                        >
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <div class="px-8 pb-8 pt-6">
                <FormUjian
                    form-id="form-ujian-modal"
                    :form="form"
                    :jenis-tes="jenisTes"
                    :bank-soal-tersaring="bankSoalTersaring"
                    :creating="creating"
                    :show-actions="false"
                    @submit="emit('submit')"
                />
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-ujian-modal"
                        :processing="form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
