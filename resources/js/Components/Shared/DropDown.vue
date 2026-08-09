<script setup>
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'
import { IconSelector, IconCheck, IconX } from '@tabler/icons-vue'
import { computed } from 'vue'

const props = defineProps({
    modelValue: { default: null },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih...' },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: '' },
    optionLabel: { type: [String, Function], default: null },
    optionValue: { type: [String, Function], default: 'id' },
    size: { type: String, default: 'md' },
    buttonClass: { type: String, default: '' },
    clearable: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'change'])

function labelOf(option) {
    if (option === null || option === undefined) return ''
    if (typeof props.optionLabel === 'function') return props.optionLabel(option)
    if (typeof props.optionLabel === 'string') return option[props.optionLabel] ?? String(option)
    if (typeof option === 'string' || typeof option === 'number') return String(option)
    return option.name || option.title || option.label || String(option.id ?? '')
}

function valueOf(option) {
    if (option === null || option === undefined) return null
    if (typeof props.optionValue === 'function') return props.optionValue(option)
    if (typeof option === 'string' || typeof option === 'number') return option
    return option[props.optionValue]
}

const selected = computed(() =>
    props.options.find(o => String(valueOf(o)) === String(props.modelValue)) || null,
)

function handleUpdate(val) {
    const v = val === null ? '' : valueOf(val)
    emit('update:modelValue', v)
    emit('change', v)
}

const sizeClasses = {
    md: 'px-4 py-3 text-body-md rounded-2xl',
    sm: 'px-3 py-2 text-body-md rounded-xl',
}
</script>

<template>
    <div>
        <label v-if="label" class="text-label-md font-medium text-primary block mb-1.5">{{ label }}</label>
        <Listbox :model-value="selected" @update:model-value="handleUpdate" :disabled="disabled">
            <div class="relative">
                <ListboxButton
                    class="w-full flex items-center justify-between gap-2 bg-surface-container-lowest border border-outline-variant text-text-body focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="[sizeClasses[size], buttonClass]"
                >
                    <span class="truncate" :class="selected ? 'text-text-body' : 'text-text-muted'">
                        {{ selected ? labelOf(selected) : placeholder }}
                    </span>
                    <span class="flex items-center gap-1 shrink-0">
                        <button
                            v-if="clearable && selected && !disabled"
                            type="button"
                            @click.stop="handleUpdate(null)"
                            class="text-text-muted hover:text-error-red transition-colors"
                            title="Hapus pilihan"
                        >
                            <IconX :size="14" />
                        </button>
                        <IconSelector :size="16" class="text-text-muted" />
                    </span>
                </ListboxButton>

                <transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <ListboxOptions
                        class="absolute z-50 mt-1.5 w-full max-h-60 overflow-auto rounded-2xl bg-surface-white border border-outline-variant shadow-soft py-1.5 focus:outline-none"
                    >
                        <ListboxOption v-slot="{ active, selected: isSelected }" v-for="o in options" :key="valueOf(o)" :value="o">
                            <div
                                class="flex items-center gap-2 px-3.5 py-2.5 cursor-pointer text-body-md transition-colors"
                                :class="active ? 'bg-secondary/10 text-primary' : 'text-text-body'"
                            >
                                <span class="flex-1 truncate">{{ labelOf(o) }}</span>
                                <IconCheck v-if="isSelected" :size="16" class="text-secondary shrink-0" />
                            </div>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
    </div>
</template>
