<script setup>
import { computed } from 'vue';

defineProps({
    modelValue: { type: String, default: "" },
    optionKeys: { type: Array, default: () => ["A", "B", "C", "D"] },
    label: { type: String, default: "Kunci Jawaban *" },
});

defineEmits(["update:modelValue"]);

const groupName = computed(() => `answer-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
    <div>
        <p class="text-label-md font-medium text-primary mb-2">{{ label }}</p>
        <div class="flex flex-wrap gap-3">
            <label
                v-for="key in optionKeys"
                :key="key"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                :class="modelValue === key ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'"
            >
                <input
                    type="radio"
                    :name="groupName"
                    :value="key"
                    :checked="modelValue === key"
                    @change="$emit('update:modelValue', key)"
                    class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary"
                />
                <span class="font-semibold" :class="modelValue === key ? 'text-secondary' : 'text-primary'">{{ key }}</span>
            </label>
        </div>
    </div>
</template>
