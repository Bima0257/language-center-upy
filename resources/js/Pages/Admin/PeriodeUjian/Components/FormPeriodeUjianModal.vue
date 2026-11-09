<script setup>
import { IconX } from '@tabler/icons-vue';
import FormPeriodeUjian from './FormPeriodeUjian.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    ujian: { type: Object, default: null },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="props.show"
        max-width="2xl"
        aria-labelledby="periode-ujian-modal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="periode-ujian-modal-title" class="text-headline-md font-bold text-text-heading">
                            {{ props.creating ? 'Tambah Periode Ujian' : 'Ubah Periode Ujian' }}
                        </h2>
                        <button
                            type="button"
                            class="p-2 text-text-muted transition-colors hover:text-primary"
                            aria-label="Tutup modal periode ujian"
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
                <FormPeriodeUjian
                    form-id="form-periode-ujian-modal"
                    :form="props.form"
                    :creating="props.creating"
                    :show-actions="false"
                    @submit="emit('submit')"
                />
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-periode-ujian-modal"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
