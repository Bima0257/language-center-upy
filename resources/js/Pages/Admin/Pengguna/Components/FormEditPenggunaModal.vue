<script setup>
import { IconX } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    roleLabel: { type: Function, required: true },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="props.show"
        max-width="lg"
        :closeable="false"
        aria-labelledby="pengguna-edit-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="pengguna-edit-modal-title" class="text-headline-md font-bold text-text-heading">Ubah Pengguna</h2>
                        <button type="button" aria-label="Tutup edit pengguna" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <form id="form-edit-pengguna" @submit.prevent="emit('submit')" class="space-y-4 px-8 pb-8 pt-6">
                <BaseFormGrid>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Nama</label>
                        <input type="text" v-model="props.form.name" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.name" class="text-error-red text-xs mt-1">{{ props.form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Email</label>
                        <input type="email" v-model="props.form.email" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="props.form.errors.email" class="text-error-red text-xs mt-1">{{ props.form.errors.email }}</p>
                    </div>
                </BaseFormGrid>
                <div>
                    <label class="text-label-md font-medium text-text-heading block mb-1.5">Role</label>
                    <select v-model="props.form.role" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
                        <option v-for="role in props.roles" :key="role" :value="role">{{ props.roleLabel(role) }}</option>
                    </select>
                </div>
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-edit-pengguna"
                        :processing="props.form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
