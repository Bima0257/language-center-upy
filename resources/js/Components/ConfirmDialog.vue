<script setup>
import { useConfirm } from '@/Composables/useConfirm'
import { IconAlertTriangle } from '@tabler/icons-vue'

const { getState, onOk, onCancel } = useConfirm()
const state = getState()
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="state.visible"
                class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/40" @click="onCancel" />

                <Transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="state.visible"
                        class="relative bg-surface-white rounded-2xl shadow-app-frame p-6 w-full max-w-md mx-auto border border-outline-variant/30"
                    >
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-950/30 flex items-center justify-center shrink-0">
                                <IconAlertTriangle :size="20" class="text-amber-600 dark:text-amber-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-title-lg text-primary font-semibold mb-1">
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
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
