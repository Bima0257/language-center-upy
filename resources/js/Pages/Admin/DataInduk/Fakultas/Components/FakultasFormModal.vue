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
    <BaseModal
        :show="props.show"
        @close="emit('close')"
        max-width="lg"
        aria-labelledby="fakultas-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="fakultas-modal-title" class="text-headline-md font-bold text-text-heading">{{ props.creating ? 'Tambah Fakultas' : 'Ubah Fakultas' }}</h2>
                         <button type="button" aria-label="Tutup form fakultas" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <form id="form-fakultas" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <BaseFormGrid>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Nama Fakultas *</label>
                        <input type="text" v-model="props.form.nama" required placeholder="Fakultas Teknik"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.nama" class="text-error-red text-xs mt-1">{{ props.form.errors.nama }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Kode *</label>
                        <input type="text" v-model="props.form.kode" required placeholder="FT"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.kode" class="text-error-red text-xs mt-1">{{ props.form.errors.kode }}</p>
                    </div>
                </BaseFormGrid>
                <BaseToggle v-model="props.form.aktif" label="Fakultas aktif" />
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-fakultas"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
