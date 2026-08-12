<script setup>
defineProps({
    width: { type: String, default: "100%" },
    height: { type: String, default: "1rem" },
    rounded: { type: String, default: "rounded-lg" },
    circle: { type: Boolean, default: false },
});
</script>

<template>
    <div
        class="bg-surface-container animate-pulse"
        :class="circle ? 'rounded-full' : rounded"
        :style="{ width: circle ? height : width, height }"
    />
</template>
