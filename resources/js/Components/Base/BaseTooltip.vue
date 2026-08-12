<script setup>
import { ref } from "vue";

const props = defineProps({
    text: { type: String, required: true },
    position: { type: String, default: "top" },
});

const visible = ref(false);

const positionClasses = {
    top: "bottom-full left-1/2 -translate-x-1/2 mb-2",
    bottom: "top-full left-1/2 -translate-x-1/2 mt-2",
    left: "right-full top-1/2 -translate-y-1/2 mr-2",
    right: "left-full top-1/2 -translate-y-1/2 ml-2",
};
</script>

<template>
    <div
        class="relative inline-flex"
        @mouseenter="visible = true"
        @mouseleave="visible = false"
    >
        <slot />
        <div
            v-if="visible"
            class="absolute z-50 whitespace-nowrap bg-primary-container text-white text-label-md px-3 py-1.5 rounded-lg shadow-soft pointer-events-none"
            :class="positionClasses[position]"
        >
            {{ text }}
        </div>
    </div>
</template>
