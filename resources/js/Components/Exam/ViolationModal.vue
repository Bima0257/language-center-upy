<script setup>
import { IconAlertTriangle, IconX } from '@tabler/icons-vue';
import { ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    message: { type: String, default: '' },
    strike: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'back']);

const countdown = ref(5);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] bg-black/50 flex items-center justify-center p-6">
            <div class="bg-white rounded-3xl p-8 shadow-app-frame max-w-md w-full">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                        <IconAlertTriangle class="text-amber-600" :size="24" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-title-lg font-semibold text-primary mb-1">Peringatan Ke-{{ strike }}</h3>
                        <p class="text-text-body text-body-md">{{ message || 'Aktivitas mencurigakan terdeteksi.' }}</p>
                        <p class="text-error-red text-label-md mt-2 font-medium">3 pelanggaran = sesi dihentikan.</p>
                        <button @click="$emit('back')"
                                class="mt-4 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                            Kembali ke Ujian
                        </button>
                    </div>
                    <button @click="$emit('close')" class="text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
