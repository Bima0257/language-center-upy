<script setup>
import { IconX, IconGridDots } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    total: { type: Number, default: 0 },
    currentIndex: { type: Number, default: 0 },
    answerByIndex: { type: Array, default: () => [] },
    answeredCount: { type: Number, default: 0 },
});

const emit = defineEmits(['navigate', 'close']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="emit('close')"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        class="bg-surface-white rounded-3xl shadow-app-frame w-full max-w-2xl overflow-hidden"
                    >
                        <!-- HEADER -->
                        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center">
                                    <IconGridDots :size="20" class="text-secondary" />
                                </div>
                                <div>
                                    <h3 class="text-title-lg font-semibold text-text-heading">Navigasi Soal</h3>
                                    <p class="text-label-md text-text-muted">
                                        {{ answeredCount }} dari {{ total }} soal terjawab
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="emit('close')"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-text-muted hover:text-text-heading hover:bg-surface-container-low transition-colors"
                            >
                                <IconX :size="20" />
                            </button>
                        </div>

                        <!-- GRID -->
                        <div class="px-6 py-6 max-h-[55vh] overflow-y-auto scroll-hide">
                            <div class="grid grid-cols-6 gap-3">
                                <button
                                    v-for="i in total"
                                    :key="i"
                                    @click="emit('navigate', i - 1)"
                                    class="relative w-full aspect-square rounded-xl flex items-center justify-center text-label-md font-bold transition-all duration-150 cursor-pointer"
                                    :class="[
                                        currentIndex === i - 1
                                            ? 'bg-primary-container text-white shadow-lg ring-2 ring-primary scale-110'
                                            : answerByIndex[i - 1]
                                                ? 'bg-green-100 text-green-700 border border-green-300 hover:scale-105'
                                                : 'bg-surface-container-low text-text-body border border-outline-variant hover:bg-surface-container-highest hover:scale-105'
                                    ]"
                                >
                                    {{ i }}
                                    <span
                                        v-if="answerByIndex[i - 1]"
                                        class="absolute -bottom-1 -right-1 text-[10px] font-bold bg-green-500 text-white rounded-full w-4 h-4 flex items-center justify-center"
                                    >
                                        {{ answerByIndex[i - 1] }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-low/50 flex items-center justify-between">
                            <div class="flex items-center gap-4 text-label-md">
                                <span class="flex items-center gap-1.5 text-text-muted">
                                    <span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span>
                                    Dijawab
                                </span>
                                <span class="flex items-center gap-1.5 text-text-muted">
                                    <span class="w-3 h-3 rounded-full bg-surface-container-highest border border-outline-variant inline-block"></span>
                                    Belum
                                </span>
                                <span class="flex items-center gap-1.5 text-text-muted">
                                    <span class="w-3 h-3 rounded-full bg-primary-container inline-block"></span>
                                    Saat ini
                                </span>
                            </div>
                            <button
                                @click="emit('close')"
                                class="px-5 py-2 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.scroll-hide::-webkit-scrollbar {
    width: 6px;
}
.scroll-hide::-webkit-scrollbar-track {
    background: transparent;
}
.scroll-hide::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}
</style>
