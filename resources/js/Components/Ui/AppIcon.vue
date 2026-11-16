<script setup>
import { computed } from 'vue';
import { iconMap } from '@/config/iconMap';

const props = defineProps({
    name: { type: String, required: true },
    filled: { type: Boolean, default: false },
    size: { type: [Number, String], default: '1em' },
});

const component = computed(() => iconMap[props.name] ?? null);
</script>

<template>
    <component
        v-if="component"
        :is="component"
        :size="size"
        :stroke-width="filled ? 2.75 : 2"
        aria-hidden="true"
    />
</template>
