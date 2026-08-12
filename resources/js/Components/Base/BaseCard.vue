<script setup>
defineProps({
    padding: { type: [Boolean, String], default: true },
});
</script>

<template>
    <div
        class="bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30"
        :class="padding === true ? 'p-6' : padding"
    >
        <slot />
    </div>
</template>
