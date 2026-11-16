<script setup>
import BaseCheckbox from '@/Components/Base/BaseCheckbox.vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    inline: { type: Boolean, default: false },
});

const model = defineModel({ type: Array, default: () => [] });

function toggled(value, checked) {
    const values = new Set(model.value ?? []);

    if (checked) {
        values.add(value);
    } else {
        values.delete(value);
    }

    model.value = [...values];
}
</script>

<template>
    <div :class="props.inline ? 'flex flex-wrap gap-4' : 'space-y-2'">
        <BaseCheckbox
            v-for="option in props.options"
            :key="String(option.value)"
            :model-value="(model ?? []).includes(option.value)"
            :label="option.label"
            :disabled="props.disabled"
            @update:model-value="toggled(option.value, $event)"
        />
    </div>
</template>
