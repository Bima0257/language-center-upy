<script setup>
import { IconCheck, IconX } from '@tabler/icons-vue';

defineProps({
    canReview: { type: Boolean, default: false },
    selectedCount: { type: Number, default: 0 },
});

defineEmits(['review', 'clear']);
</script>

<template>
    <Transition name="fade-slide">
        <div
            v-if="canReview && selectedCount > 0"
            class="fixed left-1/2 -translate-x-1/2 bottom-[76px] md:bottom-6 z-40 bg-surface-white rounded-full shadow-lg border border-outline-variant/30 px-4 py-3 flex items-center gap-3"
        >
            <span class="text-label-md font-semibold text-primary whitespace-nowrap">
                {{ selectedCount }} soal dipilih
            </span>
            <span class="w-px h-5 bg-outline-variant/40"></span>
            <BaseButton variant="success" size="sm" @click="$emit('review', 'approved')">
                <IconCheck :size="16" />Setujui
            </BaseButton>
            <BaseButton variant="danger" size="sm" @click="$emit('review', 'rejected')">
                <IconX :size="16" />Tolak
            </BaseButton>
            <BaseButton variant="secondary" size="sm" @click="$emit('clear')" class="px-3">
                Batal
            </BaseButton>
        </div>
    </Transition>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translate(-50%, 10px);
}
</style>
