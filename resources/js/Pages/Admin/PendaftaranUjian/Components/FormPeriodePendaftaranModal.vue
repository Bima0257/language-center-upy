<script setup>
import { IconX } from '@tabler/icons-vue';
import FormPeriodePendaftaran from '@/Pages/Admin/PendaftaranUjian/Components/FormPeriodePendaftaran.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    periodeUjian: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="props.show"
        max-width="2xl"
        aria-labelledby="registration-period-modal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="registration-period-modal-title" class="text-headline-md font-bold text-text-heading">
                            {{ props.creating ? 'Tambah Periode Pendaftaran' : 'Ubah Periode Pendaftaran' }}
                        </h2>
                        <button
                            type="button"
                            class="p-2 text-text-muted transition-colors hover:text-primary"
                            aria-label="Tutup modal"
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
                <FormPeriodePendaftaran
                    form-id="form-periode-pendaftaran"
                    :show-submit="false"
                    :creating="props.creating"
                    :form="props.form"
                    :periode-ujian="props.periodeUjian"
                    @submit="emit('submit')"
                />
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-periode-pendaftaran"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
