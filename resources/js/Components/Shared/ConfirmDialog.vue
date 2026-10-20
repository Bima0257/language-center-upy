<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import { IconAlertTriangle } from '@tabler/icons-vue';

const { getState, onOk, onCancel } = useConfirm();
const state = getState();
</script>

<template>
    <BaseModal :show="state.visible" max-width="md" :closeable="false">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirm-dialog-title"
            class="border border-outline-variant/30 p-6"
        >
            <div class="flex items-start gap-4 mb-6">
                <div class="w-10 h-10 rounded-full bg-warning-container flex items-center justify-center shrink-0">
                    <IconAlertTriangle :size="20" class="text-warning" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="confirm-dialog-title" class="text-title-lg text-primary font-semibold mb-1">
                        {{ state.type === 'confirm' ? 'Konfirmasi' : 'Catatan' }}
                    </h3>
                    <p class="text-body-md text-text-body">{{ state.message }}</p>
                </div>
            </div>

            <div v-if="state.type === 'prompt'" class="mb-4">
                <BaseTextarea
                    v-model="state.inputValue"
                    :placeholder="state.inputPlaceholder || 'Tulis catatan...'"
                    rows="3"
                    class="resize-none"
                />
            </div>

            <div class="flex justify-end gap-3">
                <BaseButton variant="secondary" size="xs" @click="onCancel">
                    Batal
                </BaseButton>
                <BaseButton variant="danger" size="xs" @click="onOk">
                    {{ state.type === 'confirm' ? 'Ya, Hapus' : 'Simpan' }}
                </BaseButton>
            </div>
        </div>
    </BaseModal>
</template>
