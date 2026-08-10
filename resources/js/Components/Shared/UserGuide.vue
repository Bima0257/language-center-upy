<script setup>
import Modal from '@/Components/Modal.vue';
import { IconBook2, IconCheck, IconX } from '@tabler/icons-vue';
import { ref } from 'vue';

defineProps({
    title: { type: String, default: 'Panduan' },
    steps: { type: Array, default: () => [] },
    rules: { type: Array, default: () => [] },
    buttonLabel: { type: String, default: 'Panduan' },
});

const show = ref(false);
</script>

<template>
    <button type="button" @click="show = true"
            class="flex items-center gap-1.5 px-4 py-2 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
        <IconBook2 :size="16" /> {{ buttonLabel }}
    </button>

    <Modal :show="show" max-width="2xl" @close="show = false">
        <div class="bg-surface-white rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                <h3 class="text-title-lg font-semibold text-primary flex items-center gap-2">
                    <IconBook2 :size="20" class="text-secondary" /> {{ title }}
                </h3>
                <button @click="show = false" class="p-1.5 text-text-muted hover:text-primary transition-colors">
                    <IconX :size="20" />
                </button>
            </div>

            <div class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto">
                <div v-if="steps.length">
                    <p class="text-label-md font-semibold text-primary uppercase tracking-wider mb-3">Alur Penggunaan</p>
                    <div class="space-y-3">
                        <div v-for="(step, i) in steps" :key="i" class="flex items-start gap-3">
                            <span class="w-7 h-7 shrink-0 flex items-center justify-center rounded-full bg-primary-container text-white text-label-md font-bold">{{ i + 1 }}</span>
                            <div>
                                <p class="text-body-md font-semibold text-primary">{{ step.title }}</p>
                                <p class="text-body-md text-text-body">{{ step.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="rules.length">
                    <p class="text-label-md font-semibold text-primary uppercase tracking-wider mb-3">Ketentuan</p>
                    <div class="space-y-2">
                        <p v-for="(rule, i) in rules" :key="i" class="flex items-start gap-2 text-body-md text-text-body">
                            <IconCheck :size="16" class="text-green-600 dark:text-green-400 shrink-0 mt-0.5" />
                            {{ rule }}
                        </p>
                    </div>
                </div>

                <slot name="content" />
            </div>
        </div>
    </Modal>
</template>
