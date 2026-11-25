<script setup>
import BaseTabsChrome from '@/Components/Base/BaseTabsChrome.vue';
import AppIcon from '@/Components/Ui/AppIcon.vue';

const props = defineProps({
    tabs: { type: Array, required: true },
    modelValue: { type: [String, Number], default: null },
    variant: { type: String, default: 'chrome' },
});

const emit = defineEmits(['update:modelValue']);

function isActive(tab) {
    return String(props.modelValue) === String(tab.value);
}

function selectTab(tab) {
    if (tab.disabled) return;

    emit('update:modelValue', tab.value);
}
</script>

<template>
    <BaseTabsChrome
        v-if="props.variant !== 'preview-pills'"
        :items="props.tabs"
        :model-value="props.modelValue"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <template v-if="$slots['active-action']" #active-action>
            <slot name="active-action" />
        </template>
        <template v-if="$slots.after" #after>
            <slot name="after" />
        </template>
    </BaseTabsChrome>

    <div
        v-else
        role="tablist"
        aria-orientation="horizontal"
        class="inline-flex max-w-full items-center gap-1 overflow-x-auto rounded-full border border-surface-container-high bg-surface-container-low p-1"
    >
        <button
            v-for="tab in props.tabs"
            :key="String(tab.value)"
            type="button"
            role="tab"
            :aria-selected="isActive(tab)"
            :disabled="tab.disabled"
            class="inline-flex min-h-9 shrink-0 items-center gap-2 rounded-full px-4 py-2 text-label-md font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
            :class="isActive(tab)
                ? 'bg-surface-white text-text-heading shadow-soft'
                : 'text-text-muted hover:bg-surface-container-high hover:text-text-body'"
            @click="selectTab(tab)"
        >
            <AppIcon v-if="tab.icon" :name="tab.icon" :size="16" />
            {{ tab.label }}
        </button>
    </div>
</template>
