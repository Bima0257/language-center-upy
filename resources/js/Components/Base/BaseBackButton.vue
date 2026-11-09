<script setup>
import { router } from '@inertiajs/vue3';
import { IconArrowLeft } from '@tabler/icons-vue';

const props = defineProps({
    href: { type: String, default: null },
    label: { type: String, default: 'Kembali' },
    variant: { type: String, default: 'secondary' },
    preferHistory: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

function handleClick(event) {
    emit('click', event);

    if (!props.preferHistory) return;

    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    if (props.href) router.visit(props.href);
}
</script>

<template>
    <BaseButton
        :variant="variant"
        size="sm"
        :href="preferHistory ? null : href"
        @click="handleClick"
    >
        <IconArrowLeft :size="16" />
        {{ label }}
    </BaseButton>
</template>
