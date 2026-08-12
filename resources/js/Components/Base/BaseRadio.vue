<script setup>
const props = defineProps({
    name: { type: String, required: true },
    options: { type: Array, default: () => [] },
    modelValue: { default: null },
    inline: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
    <div :class="inline ? 'flex flex-wrap gap-4' : 'space-y-2'">
        <label
            v-for="opt in options"
            :key="String(opt.value)"
            class="flex items-center gap-2.5 cursor-pointer select-none"
        >
            <input
                type="radio"
                :name="name"
                :value="opt.value"
                :checked="modelValue === opt.value"
                class="accent-secondary"
                @change="emit('update:modelValue', opt.value)"
            />
            <span class="text-text-body text-body-md">{{ opt.label }}</span>
        </label>
    </div>
</template>
