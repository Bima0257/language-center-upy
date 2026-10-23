<script setup>
import { IconEye, IconEyeOff } from '@tabler/icons-vue';

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: String, default: '' },
    icon: { type: Object, default: null },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: undefined },
    autofocus: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    error: { type: String, default: undefined },
    passwordToggle: { type: Boolean, default: false },
    showPassword: { type: Boolean, default: false },
    inputTextClass: { type: String, default: 'text-text-heading' },
});

const emit = defineEmits(['update:modelValue', 'toggle-password']);
</script>

<template>
    <div class="space-y-2">
        <div v-if="$slots.labelAction" class="flex justify-between items-center px-1">
            <label class="text-text-heading text-label-md font-medium" :for="id">{{ label }}</label>
            <slot name="labelAction" />
        </div>
        <label v-else class="text-text-heading text-label-md font-medium block ml-1" :for="id">{{ label }}</label>

        <div class="relative">
            <component :is="icon" v-if="icon" class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
            <input
                :id="id"
                :type="passwordToggle && showPassword ? 'text' : type"
                :value="modelValue"
                :required="required"
                :autofocus="autofocus"
                :autocomplete="autocomplete"
                :placeholder="placeholder"
                :class="[
                    'w-full pl-11 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]',
                    inputTextClass,
                    passwordToggle ? 'pr-12' : 'pr-4',
                ]"
                @input="emit('update:modelValue', $event.target.value)"
            />
            <button
                v-if="passwordToggle"
                type="button"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                @click="emit('toggle-password')"
            >
                <IconEye v-if="!showPassword" :size="20" />
                <IconEyeOff v-else :size="20" />
            </button>
        </div>

        <slot name="after-input" />
        <p v-if="error" class="text-error-red text-xs mt-1 ml-1">{{ error }}</p>
    </div>
</template>
