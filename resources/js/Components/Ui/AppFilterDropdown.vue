<script setup>
import { computed } from 'vue';
import AppDropdown from '@/Components/Ui/AppDropdown.vue';
import AppIcon from '@/Components/Ui/AppIcon.vue';

const props = defineProps({
    label: { type: String, required: true },
    options: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: '' },
    allOption: { type: Boolean, default: true },
    placeholder: { type: String, default: null },
    strategy: { type: String, default: 'absolute' },
    placement: { type: String, default: 'bottom' },
    hideOnScroll: { type: Boolean, default: false },
    solidBackground: { type: Boolean, default: false },
    tall: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

function optionValue(option) {
    if (option === null || option === undefined) return '';
    if (typeof option === 'string' || typeof option === 'number') return option;

    return option.value ?? option.id ?? '';
}

function optionLabel(option) {
    if (option === null || option === undefined) return '';
    if (typeof option === 'string' || typeof option === 'number') return String(option);

    return option.label ?? option.nama ?? option.name ?? String(optionValue(option));
}

const normalizedOptions = computed(() =>
    props.options.map(option => ({
        value: optionValue(option),
        label: optionLabel(option),
    })),
);

const availableOptions = computed(() => {
    if (!props.allOption) return normalizedOptions.value;

    return [{ value: '', label: 'Semua' }, ...normalizedOptions.value];
});

const selectedOption = computed(() =>
    availableOptions.value.find(option => String(option.value) === String(props.modelValue)),
);

const triggerLabel = computed(() => {
    if (selectedOption.value) return selectedOption.value.label;
    if (props.placeholder) return props.placeholder;

    return 'Pilih...';
});

function choose(option, hide) {
    emit('update:modelValue', option.value);
    emit('change', option.value);
    hide?.();
}
</script>

<template>
    <AppDropdown
        :placement="props.placement"
        :strategy="props.strategy"
        :hide-on-scroll="props.hideOnScroll"
        popper-class="min-w-48 rounded-xl border border-surface-container-high bg-surface-white p-1 shadow-soft"
    >
        <button
            type="button"
            class="inline-flex w-full items-center justify-between gap-2 rounded-full border border-surface-container-high px-4 text-body-md text-text-body transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary disabled:cursor-not-allowed disabled:opacity-50 font-semibold"
            :class="[
                props.tall ? 'h-10' : 'h-9',
                props.solidBackground ? 'bg-surface-container-low' : 'bg-surface-white',
                'hover:bg-surface-container-low',
            ]"
            :aria-label="props.label"
            aria-haspopup="menu"
        >
            <span class="truncate">{{ triggerLabel }}</span>
            <AppIcon name="chevron_down" class="shrink-0 text-text-muted" :size="14" />
        </button>

        <template #items="{ hide }">
            <div role="menu" :aria-label="props.label" class="max-h-64 overflow-y-auto modal-scrollbar">
                <button
                    v-for="option in availableOptions"
                    :key="String(option.value)"
                    type="button"
                    role="menuitemradio"
                    :aria-checked="String(option.value) === String(props.modelValue)"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-body-md transition-colors font-semibold"
                    :class="String(option.value) === String(props.modelValue)
                        ? 'bg-surface-container-low font-semibold text-text-heading'
                        : 'text-text-body hover:bg-surface-container-low'"
                    @click="choose(option, hide)"
                >
                    <span class="min-w-0 flex-1 truncate">{{ option.label }}</span>
                    <AppIcon
                        v-if="String(option.value) === String(props.modelValue)"
                        name="check_circle"
                        class="shrink-0 text-secondary"
                        :size="14"
                    />
                </button>
            </div>
        </template>
    </AppDropdown>
</template>
