<script setup>
import { IconPlus } from '@tabler/icons-vue';

defineProps({
    href: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
    label: { type: String, default: 'Tambah Data' },
});

const emit = defineEmits(['click']);
</script>

<template>
    <BaseButton
        :href="href"
        :disabled="disabled"
        :type="type"
        size="sm"
        @click="emit('click', $event)"
    >
        <IconPlus :size="16" /> {{ label }}
    </BaseButton>
</template>
