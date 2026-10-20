<script setup>
defineProps({
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: '' },
    ariaLabel: { type: String, default: 'Tab navigasi' },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="w-full">
        <div
            role="tablist"
            :aria-label="ariaLabel"
            class="flex flex-wrap items-end gap-0 px-0 pt-2"
        >
            <button
                v-for="item in items"
                :key="String(item.value)"
                type="button"
                role="tab"
                :aria-selected="String(modelValue) === String(item.value)"
                :tabindex="String(modelValue) === String(item.value) ? 0 : -1"
                class="relative min-w-28 max-w-56 truncate rounded-t-xl px-5 py-3 text-label-md font-medium transition-colors focus:z-20 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                :class="String(modelValue) === String(item.value)
                    ? '-mb-px bg-surface-white text-primary'
                    : 'text-text-muted hover:bg-surface-container hover:text-text-body'"
                @click="emit('update:modelValue', item.value)"
            >
                {{ item.label }}
            </button>
        </div>
    </div>
</template>
