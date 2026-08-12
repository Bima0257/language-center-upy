<script setup>
defineProps({
    modelValue: { type: String, default: "" },
    optionKeys: { type: Array, default: () => ["A", "B", "C", "D"] },
    label: { type: String, default: "Kunci Jawaban *" },
});

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
    <div>
        <p class="text-label-md font-medium text-primary mb-2">{{ label }}</p>
        <div class="flex flex-wrap gap-3">
            <span
                v-for="key in optionKeys"
                :key="key"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest"
            >
                <input
                    type="checkbox"
                    :checked="modelValue === key"
                    @change="$emit('update:modelValue', key)"
                    class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary"
                />
                <span class="font-semibold text-primary">{{ key }}</span>
            </span>
        </div>
    </div>
</template>
