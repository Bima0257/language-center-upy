<script setup>
import { computed } from "vue";
import { IconArrowLeft, IconArrowRight, IconMap } from "@tabler/icons-vue";

const props = defineProps({
    currentIndex: { type: Number, default: 0 },
    questions: { type: Array, default: () => [] },
    answers: { type: Object, default: () => ({}) },
    visibleNumbers: { type: Array, default: () => [] },
    stripAnim: { type: String, default: "strip-none" },
    questionNumberStart: { type: Number, default: 0 },
    canGoPrev: { type: Boolean, default: false },
    isLastStep: { type: Boolean, default: false },
});

const emit = defineEmits(["previous", "select-question", "next", "show-grid"]);

const navigationItems = computed(() => {
    const total = props.questions.length;
    const visible = [...props.visibleNumbers];

    if (total <= 7) return visible;

    const items = [];
    const hasFirst = visible.includes(0);
    const hasLast = visible.includes(total - 1);

    if (!hasFirst) items.push(0, "ellipsis-start");
    items.push(...visible);
    if (!hasLast) items.push("ellipsis-end", total - 1);

    return items.filter((item, index, collection) => {
        if (typeof item !== "number") return true;
        return collection.indexOf(item) === index;
    });
});

function onBeforeLeaveStrip(el) {
    el.style.left = `${el.offsetLeft}px`;
    el.style.top = `${el.offsetTop}px`;
}
</script>

<template>
    <div
        class="exam-question-navigation shrink-0 border-t px-2 py-1.5 sm:px-4 sm:py-2"
    >
        <div class="mx-auto grid w-full max-w-[1480px] grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-1 sm:gap-2">
            <div class="col-start-2 flex min-w-0 max-w-full items-center gap-1 sm:gap-1">
                <button
                    type="button"
                    aria-label="Soal sebelumnya"
                    @click="emit('previous')"
                    :disabled="!canGoPrev"
                    class="exam-footer-next flex h-10 w-10 shrink-0 items-center justify-center gap-1 rounded-[8px] px-0 text-xs font-semibold text-on-primary transition-all hover:bg-primary hover:text-on-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 disabled:cursor-not-allowed disabled:opacity-40 sm:h-9 sm:w-auto sm:min-w-[88px] sm:px-1.5 sm:text-xs"
                >
                    <IconArrowLeft :size="13" />
                    <span class="hidden sm:inline">Prev</span>
                </button>

                <TransitionGroup
                    :name="stripAnim"
                    tag="div"
                    class="exam-number-strip relative flex min-w-0 max-w-[60vw] flex-none items-center justify-center gap-1 overflow-x-auto py-1 sm:max-w-[420px] sm:gap-2"
                    @before-leave="onBeforeLeaveStrip"
                >
                    <template v-for="item in navigationItems" :key="String(item)">
                        <button
                            v-if="typeof item === 'number'"
                            type="button"
                            :aria-label="`Buka soal ${questionNumberStart + item}`"
                            :aria-current="item === currentIndex ? 'step' : undefined"
                            @click="emit('select-question', item)"
                            class="exam-number-button flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-semibold transition-all duration-150 motion-reduce:transition-none sm:h-9 sm:w-9"
                            :class="[
                                item === currentIndex
                                    ? 'bg-primary text-on-primary border-2 border-primary shadow-sm'
                                    : answers[questions[item]?.id] !== undefined
                                        ? 'bg-secondary/10 text-secondary hover:bg-secondary/20'
                                        : 'bg-surface-container-lowest text-text-muted hover:bg-surface-container-highest'
                            ]"
                        >
                            {{ questionNumberStart + item }}
                        </button>
                        <span
                            v-else
                            class="exam-ellipsis flex h-8 shrink-0 items-center px-1 text-sm font-bold text-text-heading sm:h-9"
                            aria-hidden="true"
                        >
                            ...
                        </span>
                    </template>
                </TransitionGroup>

                <button
                    v-if="!isLastStep"
                    type="button"
                    aria-label="Soal berikutnya"
                    @click="emit('next')"
                    class="exam-footer-next flex h-10 w-10 shrink-0 items-center justify-center gap-1 rounded-[8px] bg-primary px-0 text-xs font-semibold text-on-primary transition-all hover:bg-primary hover:text-on-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 active:scale-95 motion-reduce:transition-none sm:h-9 sm:w-auto sm:min-w-[80px] sm:px-1.5 sm:text-xs"
                >
                    <span class="hidden sm:inline">Next</span>
                    <IconArrowRight :size="13" />
                </button>
                <button
                    v-else
                    type="button"
                    aria-label="Selesaikan preview ujian"
                    @click="emit('next')"
                    class="exam-footer-next flex h-10 w-10 shrink-0 items-center justify-center gap-1 rounded-[8px] bg-primary px-0 text-xs font-semibold text-on-primary transition-all hover:bg-primary hover:text-on-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 active:scale-95 motion-reduce:transition-none sm:h-9 sm:w-auto sm:min-w-[88px] sm:px-1.5 sm:text-xs"
                >
                    <span class="hidden sm:inline">Selesai</span>
                    <IconArrowRight :size="13" />
                </button>
            </div>
            <button
                type="button"
                aria-label="Buka Peta Soal"
                title="Peta Soal"
                @click="emit('show-grid')"
                class="exam-map-button col-start-3 row-start-1 flex h-10 w-10 shrink-0 items-center justify-self-end gap-2 rounded-[10px] border text-text-heading transition-all hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/50 sm:w-auto sm:min-w-[108px] sm:px-3"
            >
                <IconMap :size="16" />
                <span class="hidden sm:inline">Peta Soal</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.exam-question-navigation {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 72%,
        transparent
    );
    background-color: var(--color-surface-white);
}

.exam-navigation-button {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 76%,
        transparent
    );
    background-color: var(--color-surface-container-lowest);
}

.exam-number-button {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 92%,
        var(--color-text-muted)
    );
}

.exam-footer-action {
    background-color: var(--color-surface-container-low);
}

.exam-footer-next {
    background-color: var(--color-primary);
}

.exam-map-button {
    border-color: color-mix(
        in srgb,
        var(--color-outline-variant) 84%,
        transparent
    );
    background-color: var(--color-surface-white);
}

.exam-ellipsis {
    color: var(--color-text-heading);
}

.exam-number-strip {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.exam-number-strip::-webkit-scrollbar {
    display: none;
}

.strip-next-move,
.strip-prev-move {
    transition: transform 0.25s ease;
}
.strip-next-enter-active {
    transition: all 0.22s ease-out;
}
.strip-next-leave-active {
    transition: all 0.18s ease-in;
    position: absolute;
}
.strip-next-enter-from {
    opacity: 0;
    transform: translateX(16px) scale(0.85);
}
.strip-next-leave-to {
    opacity: 0;
    transform: translateX(-16px) scale(0.85);
}
.strip-prev-enter-active {
    transition: all 0.22s ease-out;
}
.strip-prev-leave-active {
    transition: all 0.18s ease-in;
    position: absolute;
}
.strip-prev-enter-from {
    opacity: 0;
    transform: translateX(-16px) scale(0.85);
}
.strip-prev-leave-to {
    opacity: 0;
    transform: translateX(16px) scale(0.85);
}
</style>
