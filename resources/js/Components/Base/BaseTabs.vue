<script setup>
defineProps({
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: "" },
});

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
    <div class="inline-flex items-center gap-1 bg-surface-container rounded-full p-1">
        <button
            v-for="item in items"
            :key="String(item.value)"
            type="button"
            class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
            :class="
                modelValue === item.value
                    ? 'bg-surface-white text-primary shadow-sm'
                    : 'text-text-muted hover:text-text-body'
            "
            @click="emit('update:modelValue', item.value)"
        >
            {{ item.label }}
        </button>
    </div>
</template>
