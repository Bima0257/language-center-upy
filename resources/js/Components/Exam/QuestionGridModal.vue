<script setup>
import { computed } from 'vue';
import { IconX, IconMap } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    groups: { type: Array, default: () => [] },
    answers: { type: Object, default: () => ({}) },
    currentGlobalNumber: { type: Number, default: 0 },
});

const emit = defineEmits(['navigateGlobal', 'close']);

const totalQuestions = computed(() =>
    props.groups.reduce((sum, g) => sum + g.count, 0),
);

const answeredCount = computed(() => {
    let count = 0;
    for (const group of props.groups) {
        for (const question of group.questions) {
            if (props.answers[question.id] !== undefined) count++;
        }
    }
    return count;
});

function isAnswered(question) {
    return props.answers[question.id] !== undefined;
}

function isCurrent(number) {
    return number === props.currentGlobalNumber;
}
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
                <div
                    class="bg-surface-white rounded-3xl shadow-app-frame w-full max-w-2xl overflow-hidden max-h-[85vh] flex flex-col"
                >
                    <!-- HEADER -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center">
                                <IconMap :size="20" class="text-secondary" />
                            </div>
                            <div>
                                <h3 class="text-title-lg font-semibold text-text-heading">Peta Soal</h3>
                                <p class="text-label-md text-text-muted">
                                    {{ answeredCount }} dari {{ totalQuestions }} soal terjawab
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

                    <!-- GROUPS -->
                    <div class="px-6 py-5 overflow-y-auto scroll-hide space-y-5">
                        <div v-for="(group, gi) in groups" :key="gi">
                            <div class="flex items-center justify-between mb-2.5">
                                <span class="text-label-md font-bold uppercase tracking-wider text-text-heading">
                                    {{ group.skill.name }} · {{ group.part.name }}
                                </span>
                                <span class="text-[11px] text-text-muted font-medium">
                                    Soal {{ group.startNumber }}–{{ group.startNumber + group.count - 1 }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="question in group.questions"
                                    :key="question.id"
                                    @click="emit('navigateGlobal', question.global_number)"
                                    class="w-9 h-9 rounded-md flex items-center justify-center text-[12px] font-bold transition-all duration-150 cursor-pointer"
                                    :class="[
                                        isCurrent(question.global_number)
                                            ? 'bg-secondary text-white shadow-sm ring-2 ring-secondary/30'
                                            : isAnswered(question)
                                                ? 'bg-green-100 text-green-700 border border-green-300 hover:bg-green-200'
                                                : 'bg-surface-container-low text-text-body border border-outline-variant hover:bg-surface-container-highest'
                                    ]"
                                >
                                    {{ question.global_number }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-low/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-4 text-label-md">
                            <span class="flex items-center gap-1.5 text-text-muted">
                                <span class="w-3 h-3 rounded-full bg-secondary inline-block"></span>
                                Saat ini
                            </span>
                            <span class="flex items-center gap-1.5 text-text-muted">
                                <span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span>
                                Dijawab
                            </span>
                            <span class="flex items-center gap-1.5 text-text-muted">
                                <span class="w-3 h-3 rounded-full bg-surface-container-highest border border-outline-variant inline-block"></span>
                                Belum
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
