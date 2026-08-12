<script setup>
defineProps({
    icon: { type: Object, default: null },
    title: { type: String, default: "" },
    description: { type: String, default: "" },
});
</script>

<template>
    <div
        class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30"
    >
        <component
            :is="icon"
            v-if="icon"
            class="mx-auto text-text-muted mb-3"
            :size="48"
            stroke="1.5"
        />
        <p v-if="title" class="text-text-body text-body-md mb-1">
            {{ title }}
        </p>
        <p v-if="description" class="text-text-muted text-body-md">
            {{ description }}
        </p>
        <slot />
    </div>
</template>
