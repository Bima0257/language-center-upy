<script setup>
import { ref } from "vue";

const props = defineProps({
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: "" },
    grouped: { type: Boolean, default: true },
    ariaLabel: { type: String, default: "Tab navigasi" },
    activeAction: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);
const tabButtons = ref([]);

function isActiveTab(item) {
    return String(props.modelValue) === String(item.value);
}

function isFocusableTab(item, index) {
    if (item.disabled) return false;

    const activeIndex = props.items.findIndex(
        (candidate) => isActiveTab(candidate) && !candidate.disabled,
    );

    if (activeIndex !== -1) return activeIndex === index;

    return props.items.findIndex((candidate) => !candidate.disabled) === index;
}

function onKeydown(event, index) {
    const navigableIndexes = props.items
        .map((item, itemIndex) => (item.disabled ? -1 : itemIndex))
        .filter((itemIndex) => itemIndex !== -1);

    if (!navigableIndexes.length) return;

    const currentPosition = navigableIndexes.indexOf(index);
    if (currentPosition === -1) return;

    let nextPosition;

    if (event.key === "Home") {
        nextPosition = 0;
    } else if (event.key === "End") {
        nextPosition = navigableIndexes.length - 1;
    } else if (event.key === "ArrowRight") {
        nextPosition = (currentPosition + 1) % navigableIndexes.length;
    } else if (event.key === "ArrowLeft") {
        nextPosition = (currentPosition - 1 + navigableIndexes.length) % navigableIndexes.length;
    } else {
        return;
    }

    event.preventDefault();

    const targetIndex = navigableIndexes[nextPosition];
    tabButtons.value[targetIndex]?.focus();
    emit("update:modelValue", props.items[targetIndex].value);
}

function tabClasses(item) {
    if (props.grouped) {
        return isActiveTab(item)
            ? "bg-primary font-semibold text-on-primary"
            : "bg-transparent text-text-muted hover:bg-surface-container-high hover:text-text-body";
    }

    return isActiveTab(item)
        ? "bg-surface-white font-semibold text-primary shadow-sm"
        : "bg-transparent text-text-muted hover:bg-surface-container-low hover:text-text-body";
}
</script>

<template>
    <div
        role="tablist"
        :aria-label="props.ariaLabel"
        aria-orientation="horizontal"
        :class="[
            'tabs-scroll inline-flex max-w-full min-w-0 items-center overflow-x-auto whitespace-nowrap',
            props.grouped
                ? 'gap-1 rounded-full border border-outline-variant bg-surface-container-low p-1'
                : 'gap-1',
        ]"
    >
        <div
            v-for="(item, index) in props.items"
            :key="String(item.value)"
            role="presentation"
            class="inline-flex shrink-0 items-center"
        >
            <button
                ref="tabButtons"
                type="button"
                role="tab"
                :aria-selected="isActiveTab(item)"
                :aria-disabled="item.disabled ? 'true' : undefined"
                :disabled="item.disabled"
                :tabindex="isFocusableTab(item, index) ? 0 : -1"
                class="inline-flex min-h-10 w-auto shrink-0 items-center justify-center whitespace-nowrap rounded-full px-5 py-2.5 text-label-md font-medium transition-colors duration-150 motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                :class="[
                    tabClasses(item),
                    props.activeAction && isActiveTab(item) && $slots['active-action']
                        ? '!rounded-r-none pr-2'
                        : '',
                ]"
                @click="emit('update:modelValue', item.value)"
                @keydown="onKeydown($event, index)"
            >
                {{ item.label }}
            </button>
            <div
                v-if="props.activeAction && isActiveTab(item) && $slots['active-action']"
                class="flex h-10 w-7 shrink-0 items-center justify-center rounded-r-full bg-primary"
                @click.stop
            >
                <slot name="active-action" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.tabs-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.tabs-scroll::-webkit-scrollbar {
    display: none;
}
</style>
