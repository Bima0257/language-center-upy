<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import { IconAlertTriangle } from '@tabler/icons-vue';

const { getState, onOk, onCancel } = useConfirm();
const state = getState();
</script>

<template>
    <BaseModal
        :show="state.visible"
        max-width="md"
        fit-content
        :closeable="false"
        aria-labelledby="confirm-dialog-title"
        aria-describedby="confirm-dialog-description"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-6 py-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-warning-container flex items-center justify-center shrink-0">
                            <IconAlertTriangle :size="20" class="text-warning" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="confirm-dialog-title" class="text-title-lg text-text-heading font-semibold mb-1">
                                {{ state.type === 'confirm' ? 'Konfirmasi' : 'Catatan' }}
                            </h3>
                            <p id="confirm-dialog-description" class="text-body-md text-text-body">{{ state.message }}</p>
                        </div>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <div v-if="state.type === 'prompt'" class="px-6 pb-4 pt-4">
                <BaseTextarea
                    v-model="state.inputValue"
                    :placeholder="state.inputPlaceholder || 'Tulis catatan...'"
                    rows="3"
                    class="resize-none"
                />
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-6 py-3 flex justify-end gap-3">
                    <BaseButton variant="secondary" size="xs" @click="onCancel">
                        Batal
                    </BaseButton>
                    <BaseButton variant="danger" size="xs" @click="onOk">
                        {{ state.type === 'confirm' ? 'Ya, Hapus' : 'Simpan' }}
                    </BaseButton>
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
