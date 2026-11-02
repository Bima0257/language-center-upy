<script setup>
import { ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: '' },
    ariaLabel: { type: String, default: 'Tab navigasi' },
    activeAction: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const tabButtons = ref([]);

function onKeydown(event, index) {
    const navigableIndexes = props.items
        .map((item, itemIndex) => item.disabled ? -1 : itemIndex)
        .filter((itemIndex) => itemIndex !== -1);

    if (!navigableIndexes.length) return;

    const currentPosition = navigableIndexes.indexOf(index);
    if (currentPosition === -1) return;

    let nextPosition;

    if (event.key === 'Home') {
        nextPosition = 0;
    } else if (event.key === 'End') {
        nextPosition = navigableIndexes.length - 1;
    } else if (event.key === 'ArrowRight') {
        nextPosition = (currentPosition + 1) % navigableIndexes.length;
    } else if (event.key === 'ArrowLeft') {
        nextPosition = (currentPosition - 1 + navigableIndexes.length) % navigableIndexes.length;
    } else {
        return;
    }

    event.preventDefault();

    const targetIndex = navigableIndexes[nextPosition];
    tabButtons.value[targetIndex]?.focus();
    emit('update:modelValue', props.items[targetIndex].value);
}

function isActiveTab(item) {
    return String(props.modelValue) === String(item.value);
}

function tabClasses(item) {
    if (isActiveTab(item)) {
        return '-mb-px bg-surface-white text-text-heading shadow-soft';
    }

    return 'text-text-muted hover:bg-surface-white hover:text-text-heading hover:shadow-soft';
}
</script>

<template>
    <div class="w-full">
        <div
            role="tablist"
            :aria-label="ariaLabel"
            aria-orientation="horizontal"
            class="tabs-chrome-scroll flex flex-nowrap items-end gap-0 overflow-x-auto pt-2"
        >
            <template v-for="(item, index) in items" :key="String(item.value)">
                <div
                    role="presentation"
                    :class="[
                        'relative inline-flex shrink-0 items-end',
                        activeAction && isActiveTab(item) && $slots['active-action']
                            ? 'rounded-t-xl bg-surface-white shadow-soft'
                            : '',
                    ]"
                >
                    <button
                        ref="tabButtons"
                        type="button"
                        role="tab"
                        :aria-selected="isActiveTab(item)"
                        :disabled="item.disabled"
                        :tabindex="isActiveTab(item) && !item.disabled ? 0 : -1"
                        class="relative inline-flex h-11 min-w-28 max-w-56 shrink-0 items-center justify-center truncate rounded-t-xl pl-5 pr-5 text-label-md font-medium transition-all duration-150 focus:z-20 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                        :class="[
                            tabClasses(item),
                            activeAction && isActiveTab(item) && $slots['active-action']
                                ? '!min-w-0 rounded-tr-none pr-0'
                                : '',
                        ]"
                        @click="emit('update:modelValue', item.value)"
                        @keydown="onKeydown($event, index)"
                    >
                        {{ item.label }}
                    </button>
                    <div
                        v-if="activeAction && isActiveTab(item) && $slots['active-action']"
                        class="relative z-20 -mb-px flex h-11 w-6 shrink-0 items-center justify-center rounded-tr-xl bg-surface-white"
                        @click.stop
                    >
                        <slot name="active-action" />
                    </div>
                </div>
                <div
                    v-if="index < items.length - 1"
                    role="presentation"
                    aria-hidden="true"
                    class="flex h-11 w-2 shrink-0 items-center justify-center"
                >
                    <span class="h-4 w-px bg-outline-variant" />
                </div>
            </template>
            <div v-if="$slots.after" role="presentation" class="shrink-0">
                <slot name="after" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.tabs-chrome-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.tabs-chrome-scroll::-webkit-scrollbar {
    display: none;
}
</style>
