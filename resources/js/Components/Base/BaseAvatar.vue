<script setup>
import { computed } from "vue";

const props = defineProps({
    src: { type: String, default: null },
    name: { type: String, default: "" },
    size: { type: [String, Number], default: 40 },
    variant: { type: String, default: "pastel" },
});

const variantClasses = {
    pastel: "bg-pastel-purple text-primary",
    neutral: "bg-surface-container text-primary",
};

const initials = computed(() =>
    props.name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(""),
);
</script>

<template>
    <span
        class="inline-flex items-center justify-center rounded-full overflow-hidden font-semibold shrink-0"
        :class="variantClasses[variant]"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name"
            class="w-full h-full object-cover"
        />
        <span v-else class="text-body-md">{{ initials }}</span>
    </span>
</template>
