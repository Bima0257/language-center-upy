<script setup>
import { IconArrowLeft } from '@tabler/icons-vue';

defineProps({
    href: { type: String, default: null },
    label: { type: String, default: 'Kembali' },
    variant: { type: String, default: 'secondary' },
});

const emit = defineEmits(['click']);
</script>

<template>
    <BaseButton
        :variant="variant"
        size="sm"
        :href="href"
        @click="emit('click', $event)"
    >
        <IconArrowLeft :size="16" />
        {{ label }}
    </BaseButton>
</template>
