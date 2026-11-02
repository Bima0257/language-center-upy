<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    fakultas: { type: Array, default: () => [] },
    lockFaculty: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="props.show"
        @close="emit('close')"
        max-width="lg"
        aria-labelledby="program-studi-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="program-studi-modal-title" class="text-headline-md font-bold text-text-heading">{{ props.creating ? 'Tambah Program Studi' : 'Ubah Program Studi' }}</h2>
                         <button type="button" aria-label="Tutup form program studi" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <form id="form-program-studi" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <div>
                    <DropDown
                        v-model="props.form.fakultas_id"
                        :options="props.fakultas"
                        label="Fakultas *"
                        placeholder="Pilih fakultas"
                        option-label="nama"
                        option-value="id"
                        :disabled="props.lockFaculty"
                        empty-message="Belum ada data fakultas."
                    />
                    <p v-if="props.form.errors.fakultas_id" class="text-error-red text-xs mt-1">{{ props.form.errors.fakultas_id }}</p>
                </div>
                <BaseFormGrid>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Nama Program Studi *</label>
                        <input type="text" v-model="props.form.nama" required placeholder="Teknik Informatika"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.nama" class="text-error-red text-xs mt-1">{{ props.form.errors.nama }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Kode *</label>
                        <input type="text" v-model="props.form.kode" required placeholder="TI"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.kode" class="text-error-red text-xs mt-1">{{ props.form.errors.kode }}</p>
                    </div>
                </BaseFormGrid>
                <BaseToggle v-model="props.form.aktif" label="Program studi aktif" />
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-program-studi"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
