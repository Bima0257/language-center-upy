<script setup>
import FormTambahSoalCepat from './FormTambahSoalCepat.vue';
import { IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    quickIsAudio: { type: Boolean, default: false },
    optionKeys: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="show"
        max-width="4xl"
        aria-labelledby="add-question-modal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="add-question-modal-title" class="text-headline-md font-bold text-text-heading">Tambah Soal</h2>
                        <button
                            type="button"
                            aria-label="Tutup tambah soal"
                            class="shrink-0 p-2 text-text-muted transition-colors hover:text-primary"
                            @click="emit('close')"
                        >
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <FormTambahSoalCepat
                form-id="form-tambah-soal-materi"
                :form="form"
                :quick-is-audio="quickIsAudio"
                :option-keys="optionKeys"
                :show-actions="false"
                class="px-8 pb-8 pt-6"
                @save="emit('submit')"
                @cancel="emit('close')"
            />
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-tambah-soal-materi"
                        :processing="form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
